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

    public $distancesFromRides = [];

    public function __construct()
    {
        $this->id = self::$lastId++;
        $this->currentC = 0;
        $this->currentR = 0;
        $this->freeAt = 0;
    }

    /**
     * @param Ride $ride
     * @return int distance from the ride starting point
     */
    public function distanceFromRide(Ride $ride)
    {
        return abs($this->currentR - $ride->rStart) + abs($this->currentC - $ride->cStart);
    }

    /**
     * @param Ride $ride
     * @return int distance from the ride starting point PLUS the eventual time to wait due to the earliestStart!
     */
    public function distanceFromStartingRide(Ride $ride)
    {
        $distance = $this->distanceFromRide($ride);
        if ($this->freeAt + $distance < $ride->earliestStart)
            $distance += $ride->earliestStart - ($this->freeAt + $distance);
        return $distance;
    }

    /**
     * @param Ride $ride
     * @return int total distance (time) needed to the finish of the ride
     */
    public function distanceFromFinishingRide(Ride $ride)
    {
        return $this->distanceFromStartingRide($ride) + $ride->distance;
    }

    public function recalculateDistances($rides) {
        $this->distancesFromRides = [];
        foreach ($rides as $r) {
            $this->distancesFromRides[$r->id] = $this->distanceFromStartingRide($r);
        }
        arsort($this->distancesFromRides);
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

list($rows, $columns, $vehicles, $rides, $bonus, $steps) = explode(" ", $content[0]);
$rows = (int)$rows;
$columns = (int)$columns;
$vehicles = (int)$vehicles;
$rides = (int)$rides;
$bonus = (int)$bonus;
$steps = (int)$steps;

for ($i = 0; $i < $vehicles; $i++)
    $VEHICLES[] = new Vehicle();

foreach ($content as $k => $v) if ($k >= 1) {
    list($rStart, $cStart, $rFinish, $cFinish, $earliestStart, $latestFinish) = explode(" ", $v);
    $rStart = (int)$rStart;
    $cStart = (int)$cStart;
    $rFinish = (int)$rFinish;
    $cFinish = (int)$cFinish;
    $earliestStart = (int)$earliestStart;
    $latestFinish = (int)$latestFinish;
    $RIDES[] = new Ride($rStart, $cStart, $rFinish, $cFinish, $earliestStart, $latestFinish);
}

$VEHICLES = collect($VEHICLES);
$VEHICLES->keyBy('id');

$RIDES = collect($RIDES);
$RIDES->keyBy('id');

Log::out("Read finished");
