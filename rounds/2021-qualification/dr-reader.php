<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = @$fileName ?: 'a';

// Classes
class Street
{
    private static $lastId = 0;
    /** @var int $id */
    //public $id;
    /** @var string $name */
    public $name;
    /** @var int $duration */
    public $duration;
    /** @var Intersection $start */
    public $start;
    /** @var Intersection $end */
    public $end;
    /** @var Car[] $queue */
    public $queue = [];
    /** @var int $nSemaphorePassingCars */
    public $nSemaphorePassingCars = 0;

    public function __construct($name, $duration, $start, $end)
    {
        //$this->id = self::$lastId++;
        $this->name = $name;
        $this->duration = (int)$duration;
        $this->start = $start;
        $this->end = $end;
    }

    public function getPriority()
    {
        // TODO: ritorna la somma delle priority delle auto in queue che hanno finito la strada (ovvero che sono in coda al semaforo)
        $priority = array_reduce($this->queue, function ($carry, Car $car) {
            return $carry + $car->priority;
        }, 0);
        return $priority;
    }

    public function enqueueCar(Car $car)
    {
        array_unshift($this->queue, $car);
    }
}

class Intersection
{
    private static $lastId = 0;
    /** @var int $id */
    public $id;
    /** @var Street[] $streetsIn */
    public $streetsIn;
    /** @var Street[] $streetsOut */
    public $streetsOut;
    /** @var Street $greenStreet */
    public $greenStreet;

    public function __construct()
    {
        $this->id = self::$lastId++;
    }

    public function setGreen(Street $streetIn)
    {
        // TODO OUTPUT
        // TODO settare rosse le altre
        $this->greenStreet = $streetIn;
    }

    public function nextStep()
    {
        if ($this->greenStreet) {
            $car = array_pop($this->greenStreet->queue);
            $car->nextStreet();
        }
    }
}

class Car
{
    private static $lastId = 0;
    /** @var int $id */
    public $id;
    /** @var Street[] $streets */
    public $streets;
    /** @var Street $startingStreet */
    public $startingStreet;
    /** @var Street $currentStreet */
    //public $currentStreet;
    /** @var int $pathDuration */
    public $pathDuration;
    /** @var int $nStreets */
    public $nStreets;
    /** @var int $priority */
    public $priority;

    /** @var Street $currentStreet */
    public $currentStreet;
    /** @var int $currentStreetIdx */
    public $currentStreetIdx = 1;
    /** @var int currentStreetDuration */
    public $currentStreetDuration = 0;
    /** @var int currentStreetEnqueued */
    public $currentStreetEnqueued = false;

    public function __construct($streets)
    {
        global $STREETS;
        $this->id = self::$lastId++;
        $this->streets = $streets;
        $this->startingStreet = $streets[0];
        $this->currentStreet = $streets[0];

        $this->pathDuration = 0;
        $isFirst = true;
        foreach ($streets as $k => $street) {
            if($k < count($streets)-1)
                $STREETS[$k]->nSemaphorePassingCars++; // in the last street, the car doesn't stop at the semaphore
            if($k > 0)
                $this->pathDuration += $street->duration; // the first street is already done
        }
        $this->nStreets = count($streets);
    }

    public function calcPriority()
    {
        $this->priority = 123;
    }

    public function nextStep()
    {
        if ($this->currentStreetDuration > 0) {
            $this->currentStreetDuration--;
        } else {
            if (!$this->currentStreetEnqueued) {
                $this->enqueue();
            }
        }
    }

    public function enqueue()
    {
        if ($this->currentStreetIdx == count($this->streets)-1) { //era l'ultima strada
            $this->deinitialize();
            return;
        }
        $this->currentStreet->enqueueCar($this);
        $this->currentStreetEnqueued = true;
    }

    public function nextStreet()
    {
        $this->currentStreetIdx++;
        $this->currentStreet = $this->streets[$this->currentStreetIdx];
        $this->currentStreetDuration = $this->currentStreet->duration;
        $this->currentStreetEnqueued = false;
    }

    public function deinitialize()
    {
        global $CARS;
        global $SCORE;
        global $T;
        global $BONUS;
        global $DURATION;
        $SCORE += $BONUS + ($DURATION - $T);
        $CARS->forget($this->id);
    }
}

// Variables
$DURATION = 0;
$N_INTERSECTIONS = 0;
$N_STREETS = 0;
$N_CARS = 0;
$INTERSECTIONS = [];
$STREETS = [];
$CARS = [];
$BONUS = 0;

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($DURATION, $N_INTERSECTIONS, $N_STREETS, $N_CARS, $BONUS) = explode(" ", $content[0]);
$BONUS = (int)$BONUS;
$N_CARS = (int)$N_CARS;
$N_STREETS = (int)$N_STREETS;
$N_INTERSECTIONS = (int)$N_INTERSECTIONS;
$DURATION = (int)$DURATION;
$streetIdxStart = 1;
$streetIdxEnd = $streetIdxStart + $N_STREETS - 1;
$carsIdxStart = $streetIdxEnd + 1;
$carsIdxEnd = $carsIdxStart + $N_CARS - 1;

for ($i = 0; $i < $N_INTERSECTIONS; $i++)
    $INTERSECTIONS[$i] = new Intersection();

for ($streetIdx = $streetIdxStart; $streetIdx <= $streetIdxEnd; $streetIdx++) {
    list($start, $end, $name, $duration) = explode(" ", $content[$streetIdx]);
    $STREETS[$name] = new Street($name, $duration, $INTERSECTIONS[(int)$start], $INTERSECTIONS[(int)$end]);
}

for ($carsIdx = $carsIdxStart; $carsIdx <= $carsIdxEnd; $carsIdx++) {
    $c = explode(" ", $content[$carsIdx]);
    $streets = [];
    foreach ($c as $k => $v) {
        if ($k > 0) {
            $streets[] = $STREETS[$v];
        }
    }
    $CARS[] = new Car($streets);
}

foreach ($STREETS as $street) {
    /** @var Street $street */
    $street->start->streetsOut[] = $street;
    $street->end->streetsIn[] = $street;
}

Log::out("Read finished");
