<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

global $snakes;
global $map;
global $rowsCount;
global $columnsCount;
global $snakesCount;

$fileName = 'f';

include __DIR__ . '/reader.php';

$analyzer = new Analyzer($fileName, [
    'rows' => $rowsCount,
    'columns' => $columnsCount,
    'snakes' => $snakesCount,
]);

$analyzer->addDataset('snakes', $snakes, ['length']);

$analyzer->analyze();

die();
