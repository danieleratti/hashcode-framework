<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = @$fileName ?: 'a';

// Classes
class Foo
{
    private static $lastId = 0;
    /** @var int $id */
    //public $id;
    /** @var string $name */
    public $name;
    /** @var Foo[] $queue */
    public $queue = [];

    public function __construct()
    {
        //$this->id = self::$lastId++;
        //$this->name = $name;
    }
}

// Variables
$FOO = 0;
$FOOS = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($foo1, $foo2) = explode(" ", $content[0]);
$foo1 = (int)$foo1;

Log::out("Read finished");
