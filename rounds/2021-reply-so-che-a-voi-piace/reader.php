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
    /** @var int[] $positions */
    public $positions = [];
    /** @var int $latencyWeight */
    public $latencyWeight = 0;
    /** @var int $speedWeight */
    public $speedWeight = 0;

    public function __construct($id, $positions, $latencyWeight, $speedWeight)
    {
        $this->id = $id;
        $this->positions = $positions;
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
$FOOS = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($foo1, $foo2) = explode(" ", $content[0]);
$foo1 = (int)$foo1;

Log::out("Read finished");
