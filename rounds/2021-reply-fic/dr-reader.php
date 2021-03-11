<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once __DIR__ . '/../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = @$fileName ?: 'a';

// Classes
class Building
{
    private static $lastId = 0;

    /** @var int $id */
    public $id;
    /** @var int $r */
    public $r;
    /** @var int $c */
    public $c;
    /** @var int $latency */
    public $latency;
    /** @var int $speed */
    public $speed;

    public function __construct($r, $c, $latency, $speed)
    {
        $this->id = self::$lastId++;
        $this->r = $r;
        $this->c = $c;
        $this->latency = $latency;
        $this->speed = $speed;
    }
}

class Antenna
{
    private static $lastId = 0;

    /** @var int $id */
    public $id;
    /** @var int $range */
    public $range;
    /** @var int $speed */
    public $speed;

    public function __construct($range, $speed)
    {
        $this->id = self::$lastId++;
        $this->range = $range;
        $this->speed = $speed;
    }
}

// Variables
$FOO = 0;
$FOOS = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$W, $H] = explode(" ", $content[0]);
$W = (int)$W;
$H = (int)$H;
[$buildingsCount, $antennasCount, $reward] = explode(" ", $content[1]);
$buildingsCount = (int)$buildingsCount;
$antennasCount = (int)$antennasCount;
$reward = (int)$reward;
array_splice($content, 0, 2);

// Buildings
$buildingsCount = (int)$content[0];
/** @var Building[] $BUILDINGS */
$BUILDINGS = [];
for ($i = 0; $i < $buildingsCount; $i++) {
    [$c, $r, $latency, $speed] = explode(' ', $content[$i]);
    $b = new Building($r, $c, $latency, $speed);
    $BUILDINGS[$b->id] = $b;
}
array_splice($content, 0, $buildingsCount);

// Antennas
/** @var Antenna[] $ANTENNAS */
$ANTENNAS = [];
for ($i = 0; $i < $antennasCount; $i++) {
    [$range, $speed] = explode(' ', $content[$i]);
    $a = new Antenna($range, $speed);
    $ANTENNAS[$a->id] = $a;
}
array_splice($content, 0, $antennasCount);

Log::out("Read finished");
