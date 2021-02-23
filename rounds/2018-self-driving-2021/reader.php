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
    public $id;
    public $currentR;
    public $currentC;
    public $freeAt;

    public function __construct()
    {
        $this->id = self::$lastId++;
        $this->currentC = 0;
        $this->currentR = 0;
        $this->freeAt = 0;
    }
}

class Ride
{
    private static $lastId = 0;
    public $id;
    public $rStart;
    public $cStart;
    public $rFinish;
    public $cFinish;
    public $earliestStart;
    public $latestFinish;
    public $distance;

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

for($i=0;$i<$vehicles;$i++)
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
