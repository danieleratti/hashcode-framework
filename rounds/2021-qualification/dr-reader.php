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
    public $id;
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
        $this->id = self::$lastId++;
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

    public function __construct()
    {
        $this->id = self::$lastId++;
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

    public function __construct($streets)
    {
        $this->id = self::$lastId++;
        $this->streets = $streets;
        $this->startingStreet = $streets[0];
    }
}

// Variables
$DURATION = 0;
$N_INTERSECTIONS = 0;
$N_STREETS = 0;
$N_CARS = 0;
$STREETS = [];
$CARS = [];
$BONUS = 0;

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($DURATION, $N_INTERSECTIONS, $N_STREETS, $N_CARS, $BONUS) = explode(" ", $content[0]);
$streetIdxStart = 1;
$streetIdxEnd = $streetIdxStart + $N_STREETS;
$carsIdxStart = $streetIdxEnd + 1;
$carsIdxEnd = $carsIdxStart + $N_CARS;

for ($streetIdx = $streetIdxStart; $streetIdx <= $streetIdxEnd; $streetIdx++) {
    //$foo[] = explode(" ", $v);
}

for ($carsIdx = $carsIdxStart; $carsIdx <= $carsIdxEnd; $streetIdx++) {
    //$foo[] = explode(" ", $v);
}

$foo = collect($foo);
$foo->keyBy('id');

Log::out("Read finished");
