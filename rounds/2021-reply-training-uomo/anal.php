<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Graph;

$fileName = 'f';

include 'reader-seb.php';

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

$analyzer = new Analyzer($fileName, [
    'width' => $width,
    'height' => $height,
    'numDevs' => $numDevs,
    'numProjManager' => $numProjManager,
]);
$analyzer->addDataset('developers', $developers, ['bonus', 'skills']);
$analyzer->addDataset('managers', $managers, ['bonus']);
$analyzer->addDataset('companies', $companies, []);
$analyzer->analyze();
