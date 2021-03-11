<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = @$fileName ?: 'a';

// Classes
class Building
{
    /** @var int $id */
    public $id;
    /** @var int $r */
    public $r = [];
    /** @var int $c */
    public $c = [];
    /** @var int $latencyWeight */
    public $latencyWeight = 0;
    /** @var int $speedWeight */
    public $speedWeight = 0;

    public function __construct($id, $r, $c, $latencyWeight, $speedWeight)
    {
        $this->id = $id;
        $this->r = $r;
        $this->c = $c;
        $this->latencyWeight = $latencyWeight;
        $this->speedWeight = $speedWeight;
    }
}

class Antenna
{
    /** @var int $id */
    public $id;
    /** @var int $range */
    public $range = 0;
    /** @var int $speed */
    public $speed = 0;
    /** @var int $r */
    public $r = 0;
    /** @var int $c */
    public $c = 0;

    public function __construct($id, $range, $speed)
    {
        $this->id = $id;
        $this->range = $range;
        $this->speed = $speed;
    }

    // public function distance($building) {
    //     return abs($this->currentR - $ride->rStart) + abs($this->currentC - $ride->cStart);
    // }

    // public function calculateScore($building) {
    // }
}

// Variables
$H = 0;
$W = 0;
$totalBuildings = 0;
$totalAntennas = 0;
$finalReward = 0;
$buildings = [];
$antennas = [];

// Reading the inputs
Log::out("Reading file " . $fileName);
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($W, $H) = explode(" ", $content[0]);

list($totalBuildings, $totalAntennas, $finalReward) = explode(" ", $content[1]);

$offset = 2;
for($i = 0; $i < $totalBuildings; $i++) {
    list($x, $y, $latencyW, $connectionW) = explode(" ", $content[$offset + $i]);
    $buildings[] = new Building($i, $x, $y, $latencyW, $connectionW);
}

$offset = 2 + $totalBuildings;
for($i = 0; $i < $totalAntennas; $i++) {
    list($range, $connectionS) = explode(" ", $content[$offset + $i]);
    $antennas[] = new Antenna($i, $range, $connectionS);
}

Log::out("Read finished");
