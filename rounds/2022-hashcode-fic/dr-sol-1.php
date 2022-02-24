<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use Utils\Serializer;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;

/* Config & Pre runtime */
$fileName = 'a';
$param1 = 1;

Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);
Autoupload::init();

/* Reader */
include_once 'dr-reader.php';

/* Vars */
$SCORE = 0;

/* Functions */

/* Runtime */

$output = "...";
$fileManager->outputV2($output);
//Log::out("Uploading!", 0, "green");
//Autoupload::submission($fileName, null, $output);
