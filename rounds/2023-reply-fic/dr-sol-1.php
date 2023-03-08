<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Contributor[] */
global $contributors;
/** @var Project[] */
global $projects;

/* Config & Pre runtime */
$fileName = 'a';
#$param1 = 1;
#Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'dr-reader.php';

/* Functions */
function getOutput()
{
    $output = [];
    $output[] = "a b c";
    return implode("\n", $output);
}

/* Vars */
$SCORE = 0;

/* Runtime */
#ArrayUtils::array_keysort_objects($remainingProjects, 'score', SORT_DESC);
$fileManager->outputV2(getOutput(), $SCORE);

//Log::out("Uploading!", 0, "green");
