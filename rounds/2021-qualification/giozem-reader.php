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

    public function __construct($name, $duration, $start, $end)
    {
        //$this->id = self::$lastId++;
        $this->name = $name;
        $this->duration = (int)$duration;
        $this->start = $start;
        $this->end = $end;
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
    /** @var array $semaphoreToTime */
    public $semaphoreToTime;
    /** @var array $streetToCongestion */
    public $streetToCongestion;
    /** @var array $streetToScore */
    public $streetToScore;

    public function __construct()
    {
        $this->id = self::$lastId++;
        $this->semaphoreToTime = [];
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

    public function __construct($streets)
    {
        $this->id = self::$lastId++;
        $this->streets = $streets;
        $this->startingStreet = $streets[0];

        $this->pathDuration = 0;
        $isFirst = true;
        foreach ($streets as $street) {
            if ($isFirst) {
                // Ignore the first street because the car is already at the street end
                $isFirst = false;
                continue;
            }
            $this->pathDuration += $street->duration;
        }
        $this->nStreets = count($streets);
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
$carsIdxStart = $streetIdxEnd;
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
