<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = @$fileName ?: 'a';


// Classes
class Vehicle
{
    private static $lastId = 0;
    /** @var int $id */
    public $id;
    /** @var int $currentR */
    public $currentR;
    /** @var int $currentC */
    public $currentC;
    /** @var int $freeAt */
    public $freeAt;

    public function __construct()
    {
        $this->id = self::$lastId++;
        $this->currentC = 0;
        $this->currentR = 0;
        $this->freeAt = 0;
    }

    public function distanceFromRide($ride) {
        return abs($this->currentR - $ride->rStart) + abs($this->currentC - $ride->cStart);
    }
}

class Ride
{
    private static $lastId = 0;
    /** @var int $id */
    public $id;
    /** @var int $rStart */
    public $rStart;
    /** @var int $cStart */
    public $cStart;
    /** @var int $rFinish */
    public $rFinish;
    /** @var int $cFinish */
    public $cFinish;
    /** @var int $earliestStart */
    public $earliestStart;
    /** @var int $latestFinish */
    public $latestFinish;
    /** @var int $distance */
    public $distance;
    /** @var int $timespan */
    public $timespan;

    public function __construct($rStart, $cStart, $rFinish, $cFinish, $earliestStart, $latestFinish)
    {
        $this->id = self::$lastId++;
        $this->rStart = $rStart;
        $this->rFinish = $rFinish;
        $this->cStart = $cStart;
        $this->cFinish = $cFinish;
        $this->earliestStart = $earliestStart;
        $this->latestFinish = $latestFinish;
        $this->distance = abs($rFinish - $rStart) + abs($cFinish - $cStart);
        $this->timespan = $latestFinish - $earliestStart;
    }
}

// Variables
$VEHICLES = [];
$RIDES = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$rows, $columns, $vehicles, $rides, $bonus, $steps] = explode(" ", $content[0]);
$rows = (int)$rows;
$columns = (int)$columns;
$vehicles = (int)$vehicles;
$rides = (int)$rides;
$bonus = (int)$bonus;
$steps = (int)$steps;

for ($i = 0; $i < $vehicles; $i++) {
    $v = new Vehicle();
    $VEHICLES[$v->id] = $v;
}

foreach ($content as $k => $v) if ($k >= 1) {
    [$rStart, $cStart, $rFinish, $cFinish, $earliestStart, $latestFinish] = explode(" ", $v);
    $rStart = (int)$rStart;
    $cStart = (int)$cStart;
    $rFinish = (int)$rFinish;
    $cFinish = (int)$cFinish;
    $earliestStart = (int)$earliestStart;
    $latestFinish = (int)$latestFinish;
    $r = new Ride($rStart, $cStart, $rFinish, $cFinish, $earliestStart, $latestFinish);
    $RIDES[$r->id] = $r;
}

Log::out("Read finished");
