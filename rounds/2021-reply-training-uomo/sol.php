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
/** @var int[] $companies */
/** @var string[][] $office */
/** @var int $width */
/** @var int $height */
/** @var int $numDevs */
/** @var int $numProjManager */

$SCORE = 0;

/* ALGO */
Log::out("Run with fileName $fileName");
$descCompanies = $companies;
array_multisort($descCompanies, SORT_DESC, array_keys($descCompanies));
$mostPopularCompany = array_keys($descCompanies)[0];
$ascCompanies = $companies;
array_multisort($ascCompanies, SORT_ASC, array_keys($descCompanies), 'bonus');
$worstPopularCompany = array_keys($ascCompanies)[0];
$keys = array_keys($developers);
array_multisort(array_column($developers, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers, $keys);
$developers = array_combine($keys, $developers);

// Ordino i Managers per bonus desc
$keys = array_keys($managers);
array_multisort(
    array_column($managers, 'bonus'), SORT_DESC, SORT_NUMERIC, $managers, $keys
);

$managers = array_combine($keys, $managers);


/* OUTPUT */
Log::out('Output...');
// $fileManager->outputV2($output, 'score_' . $SCORE);
// Autoupload::submission($fileName, null, $output);
