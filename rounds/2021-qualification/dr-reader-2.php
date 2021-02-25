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
    /** @var int $priority */
    public $priority = 0;

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
    /** @var float $priority */
    public $priority=0;

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
        global $OVERHEADQUEUE;
        $this->id = self::$lastId++;
        $this->streets = $streets;
        $this->startingStreet = $streets[0];
        $this->currentStreet = $streets[0];

        $this->pathDuration = 0;
        $isFirst = true;
        foreach ($streets as $street) {
            if ($isFirst) {
                // Ignore the first street because the car is already at the street end
                $isFirst = false;
                continue;
            }
            $this->pathDuration += $street->duration + $OVERHEADQUEUE;
        }
        $this->nStreets = count($streets);
    }

    public function calcPriority($confirm=false)
    {
        global $BONUS, $EXP;
        //$this->priority = $BONUS / ($this->pathDuration + pow($this->nStreets, 2));
        //$this->priority = 1 / pow($this->pathDuration * pow($this->nStreets, 0.25), $EXP);
        //$this->priority = 1 / pow($this->nStreets, $EXP);
        //$this->priority = 1 / pow($this->pathDuration, $EXP);
        //$this->priority = $BONUS / pow($this->pathDuration, $EXP);
        $this->priority = 1;
        if($confirm) {
            foreach ($this->streets as $k => $street) {
                if ($k < count($this->streets) - 1) {
                    $street->priority += $this->priority;
                }
            }
        }
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
