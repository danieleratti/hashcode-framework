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
Cerberus::runClient(['fileName' => 'b', 'param1' => 1.0]);
// Autoupload::init();

include 'reader-seb.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Employee[] $employees */
/** @var Employee[] $developers */
/** @var Employee[] $managers */
/** @var string[][] $office */
/** @var int $width */
/** @var int $height */
/** @var int $numDevs */
/** @var int $numProjManager */

$SCORE = 0;

/* ALGO */
Log::out("Run with fileName $fileName");


/* OUTPUT */
Log::out('Output...');
// $fileManager->outputV2($output, 'score_' . $SCORE);
// Autoupload::submission($fileName, null, $output);
