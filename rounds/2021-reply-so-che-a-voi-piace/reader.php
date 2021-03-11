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
    public $r = null;
    /** @var int $c */
    public $c = null;
    /** @var int $latencyWeight */
    public $latencyWeight;
    /** @var int $speedWeight */
    public $speedWeight;

    public $covered = false;

    public function __construct($id, $c, $r, $latencyWeight, $speedWeight)
    {
        $this->id = $id;
        $this->r = $r;
        $this->c = $c;
        $this->latencyWeight = $latencyWeight;
        $this->speedWeight = $speedWeight;
        $this->covered = false;
    }
}

class Antenna
{
    /** @var int $id */
    public $id;
    /** @var int $range */
    public $range;
    /** @var int $speed */
    public $speed;
    /** @var int $r */
    public $r = null;
    /** @var int $c */
    public $c = null;

    public function __construct($id, $range, $speed)
    {
        $this->id = $id;
        $this->range = $range;
        $this->speed = $speed;
    }

    /**
     * @return int score della coppia antenna - building piazziando l'antenna a $r $c
     */
    public function score($building, $r, $c) {
        return ($building->speedWeight * $this->speed) - ($building->latencyWeight * distance($r, $c, $building->r, $building->c));
    }

    /**
     * @return boolean Se l'antenna è stata piazzata
     */
    public function placed() {
        return $this->r != null && $this->c != null;
    }
}

function distance($r1, $c1, $r2, $c2) {
    return abs($r1 - $c1) + abs($r2 - $c2);
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
