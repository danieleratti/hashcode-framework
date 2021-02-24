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
    public $id;
    public function __construct()
    {
        $this->id = self::$lastId++;
    }
}

// Variables
$foo = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($foo1) = explode(" ", $content[0]);
foreach ($content as $k => $v) if ($k >= 1) {
    $foo[] = explode(" ", $v);
    // ...
}

$foo = collect($foo);
$foo->keyBy('id');

Log::out("Read finished");
