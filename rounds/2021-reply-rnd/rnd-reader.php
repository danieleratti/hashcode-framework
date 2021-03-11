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
    private static $lastId = 0;

    /** @var int $id */
    public $id;
    /** @var int $name */
    public $latency;
    /** @var int $connectionSpeedWeight */
    public $connectionSpeedWeight;

    /** @var Cell $cell */
    public $cell;

    public function __construct($latency, $connectionSpeedWeight)
    {
        $this->id = self::$lastId++;
        $this->latency = $latency;
        $this->connectionSpeedWeight = $connectionSpeedWeight;
    }
}

class Antenna
{
    private static $lastId = 0;

    /** @var int $id */
    public $id;
    /** @var int $range */
    public $range;
    /** @var int $connectionSpeed */
    public $connectionSpeed;

    /** @var Cell $cell */
    public $cell;

    public function __construct($range, $connectionSpeed)
    {
        $this->id = self::$lastId++;
        $this->range = $range;
        $this->connectionSpeed = $connectionSpeed;
    }
}

class Square{
    /** @var Cell $upperRight*/
    public $upperRight;
    /** @var Cell $upperLeft*/
    public $upperLeft;
    /** @var Cell $lowerRight*/
    public $lowerRight;
    /** @var Cell $lowerLeft*/
    public $lowerLeft;

    public function __construct( $ul, $ur, $ll, $lr){
        $this->lowerRight=$lr;
        $this->lowerLeft=$ll;
        $this->upperLeft=$ul;
        $this->upperRight=$ur;

    }

}

class Map
{
    /** @var Cell[][] $map */
    public $map;
    /** @var int $width */
    public $width;
    /** @var int $height */
    public $height;

    public function __construct($map, $height, $width)
    {
        $this->map = $map;
        $this->height = $height;
        $this->width = $width;
    }
}

class Cell
{
    /** @var int $x */
    public $x;
    /** @var int $y */
    public $y;

    /** @var Building $building */
    public $building;
    /** @var Antenna $antenna */
    public $antenna;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}


// Variables
$BUILDINGS = [];
$ANTENNAS = [];
$map = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($WIDTH, $HEIGHT) = explode(" ", $content[0]);

list($NBUILDINGS, $NANTENNAS, $REWARD) = explode(" ", $content[1]);

for ($i = 0; $i < $NBUILDINGS; $i++) {
    list($x, $y, $latency, $weight) = explode(" ", $content[2 + $i]);
    $cell = new Cell($x, $y);
    $building = new Building($latency, $weight);
    $building->cell = $cell;
    $cell->building = $building;
    $BUILDINGS[] = $building;
    $map[$x][$y] = $cell;
}

for ($i = 0; $i < $NANTENNAS; $i++) {
    list($range, $speed) = explode(" ", $content[2 + $BUILDINGS + 1 + $i]);
    $antenna = new Antenna($range, $speed);
    $ANTENNAS[] = $antenna;
}

$MAP = new Map($map, $HEIGHT, $WIDTH);
$BUILDINGS = collect($BUILDINGS)->keyBy('id');
$ANTENNAS = collect($ANTENNAS)->keyBy('id');

Log::out("Read finished");
