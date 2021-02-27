<?php

require_once '../../bootstrap.php';

use Utils\Log;

class Street
{
    // Base attrs
    /** @var string $name */
    public $name;
    /** @var int $duration */
    public $duration;
    /** @var Intersection $start */
    public $start;
    /** @var Intersection $end */
    public $end;
    /** @var Semaphore $semaphore */
    public $semaphore;

    public function __construct($name, $duration, $start, $end)
    {
        $this->name = $name;
        $this->duration = (int)$duration;
        $this->start = $start;
        $this->end = $end;
        $this->semaphore = new Semaphore($this);

        $this->end->streetsIn[] = $this;
        $this->start->streetsOut[] = $this;

        $this->init();
    }

    public function init()
    {
        $this->semaphore->init();
    }
}

class Semaphore
{
    // Base attrs
    /** @var Street $street */
    public $street;
    /** @var Intersection $intersection */
    public $intersection;

    // Instant attrs
    /** @var Car[] $queue */
    public $queue;
    /** @var int $timeDuration */
    public $timeDuration;

    // History attrs
    /** int $maxQueue */
    public $maxQueue;

    public function __construct(Street $street)
    {
        $this->street = $street;
        $this->intersection = $street->end;
        $this->init();
    }

    public function init()
    {
        $this->timeDuration = 1;
    }

    public function getLoad()
    {
        return array_reduce($this->queue, function ($carry, Car $car) {
            return $carry + $car->priority;
        }, 0);
    }

    public function enqueueCar(Car $car)
    {
        array_unshift($this->queue, $car);
        $this->maxQueue = max(count($this->queue), $this->maxQueue);
    }

}

class Intersection
{
    // Base attrs
    public $id;
    /** @var Street[] $streetsIn */
    public $streetsIn = [];
    /** @var Street[] $streetsOut */
    public $streetsOut = [];

    // History attrs
    /** @var Street[] $greenScheduling */
    public $greenScheduling = [];

    public function __construct($id)
    {
        $this->id = $id;
        $this->init();
    }

    public function init()
    {
        $this->greenScheduling = [];
    }

    public function updateScheduling()
    {
        $this->greenScheduling = [];
        foreach ($this->streetsIn as $streetName => $street) {
            for ($i = 0; $i < $street->semaphore->timeDuration; $i++) {
                $this->greenScheduling[] = $this->streetsIn[$streetName];
            }
        }
    }

    public function nextStep(int $t)
    {
        if (!$this->greenScheduling) return;
        $currentStreet = $this->greenScheduling[$t % count($this->greenScheduling)];
        if ($currentStreet) {
            if ($currentStreet->semaphore->queue) {
                $car = array_pop($currentStreet->semaphore->queue);
                $car->nextStreet();
            }
        } else {
            Log::out('Non c\'è una strada verde');
        }
    }
}

class Car
{
    private static $lastId = 0;

    // Base attrs
    /** @var int $id */
    public $id;
    /** @var Street[] $streets */
    public $streets;
    /** @var Street $startingStreet */
    public $startingStreet;

    // General attrs
    /** @var int $pathDuration */
    public $pathDuration;
    /** @var int $priority */
    public $priority;

    // Instant attrs
    /** @var Street $currentStreet */
    public $currentStreet;
    /** @var int $currentStreetIdx */
    public $currentStreetIdx = 0;
    /** @var int currentStreetDuration */
    public $currentStreetDuration = 0;
    /** @var int currentStreetEnqueued */
    public $currentStreetEnqueued = false;

    public function __construct($streets)
    {
        $this->id = self::$lastId++;
        $this->streets = $streets;
        $this->init();

        $this->pathDuration = 0;
        foreach ($streets as $street) {
            /** @var Street $street */
            if ($street !== $this->startingStreet) {
                $this->pathDuration += $street->duration;
            }
        }
    }

    public function init()
    {
        $this->currentStreetIdx = 0;
        $this->startingStreet = $this->streets[$this->currentStreetIdx];
        $this->currentStreet = $this->startingStreet;
    }

    public function nextStep()
    {
        if ($this->currentStreetDuration > 0) {
            $this->currentStreetDuration--;
        } else {
            if (!$this->currentStreetEnqueued) {
                return $this->enqueue();
            }
        }
        return false;
    }

    public function enqueue()
    {
        if ($this->currentStreetIdx == count($this->streets) - 1) { //era l'ultima strada
            return true;
        }
        $this->currentStreet->enqueueCar($this);
        $this->currentStreetEnqueued = true;
        return false;
    }

    public function nextStreet()
    {
        $this->currentStreetIdx++;
        $this->currentStreet = $this->streets[$this->currentStreetIdx];
        $this->currentStreetDuration = $this->currentStreet->duration;
        $this->currentStreetEnqueued = false;
    }
}
