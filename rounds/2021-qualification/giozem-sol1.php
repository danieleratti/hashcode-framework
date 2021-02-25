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

include 'giozem-reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Collection|Street[] $STREETS */
/** @var Collection|Intersection[] $INTERSECTIONS */
/** @var Collection|Car[] $CARS */
/** @var int $DURATION */
/** @var int $N_INTERSECTIONS */
/** @var int $N_STREETS */
/** @var int $N_CARS */
/** @var int $BONUS */

$SCORE = 0;


/* ALGO */
Log::out("Run with fileName $fileName");
$SCORE = $param1;

// Filtro le car che non hanno streets
$CARS = array_filter($CARS, function ($c) {
    return count($c->streets) > 0;
});


/* OUTPUT */
Log::out('Output...');
$output = "xxx";
//$fileManager->outputV2($output, 'score_' . $SCORE);
//Autoupload::submission($fileName, null, $output);
