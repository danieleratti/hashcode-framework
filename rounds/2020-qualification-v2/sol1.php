<?php

use Utils\Collection;
use Utils\Log;
use Utils\Analysis\Analyzer;

$fileName = 'e';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var Books[] $books */
/** @var Libraries[] $libraries */



$analyzer = new Analyzer($fileName, [
    'books_count' => count($books),
    'libraries_count' => count($libraries),
]);
/*$analyzer->addDataset('books', $books, ['award', 'inLibraries']);
$analyzer->addDataset('libraries', $libraries, ['signUpDuration', 'shipsPerDay', 'books']);
$analyzer->analyze();*/

$analyzer->addDataset('Books', $books->toArray(), ['award', 'inLibrariesCount']);

$analyzer->analyze();

