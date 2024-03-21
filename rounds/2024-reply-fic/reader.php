<?php

global $fileName;

use Utils\FileManager;

require_once '../../bootstrap.php';

class Demo
{
    public string $name;
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;


print_r($content);
