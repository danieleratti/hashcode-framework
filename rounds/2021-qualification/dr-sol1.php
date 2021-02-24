<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = null;
$param1 = null;
Cerberus::runClient(['fileName' => 'a' /*, 'param1' => 1.0*/]);
Autoupload::init();

include 'dr-reader.php';


/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Foo[] $foo */

$SCORE = 0;


/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = $param1;


/* OUTPUT */
$output = "xxx";
$fileManager->output($output, $fileName . '__' . $SCORE);
//Autoupload::submission($fileName, null, $output);
