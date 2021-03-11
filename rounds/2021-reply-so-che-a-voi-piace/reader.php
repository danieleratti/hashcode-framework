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
    public $positions = [];
    public $latency = 0;
    public $speed = 0;

    public function __construct($id, $positions, $latency, $speed)
    {
        $this->id = $id;
        $this->positions = $positions;
        $this->latency = $latency;
        $this->speed = $speed;
    }
}

class Antenna
{
    public $range = 0;
    public $speed = 0;

    public function __construct($id, $range, $speed)
    {
        $this->id = $id;
        $this->range = $range;
        $this->speed = $speed;
    }
}

// Variables
$H = 0;
$W = 0;
$totalBuildings = 0;
$FOOS = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($W, $H) = explode(" ", $content[0]);

list($totalBuildings, $totalAntennas, $finalReward) = explode(" ", $content[1]);



Log::out("Read finished");
