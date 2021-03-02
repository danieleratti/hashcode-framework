<?php

use Utils\Analysis\Analyzer;

require_once '../../bootstrap.php';

$fileName = 'a';


$analyzer = new Analyzer($fileName, [
    'books_count' => count($books),
    'libraries_count' => count($libraries),
    'max_days' => $countDays,
]);

$analyzer->addDataset('books', $books, ['award', 'inLibraries']);
$analyzer->addDataset('libraries', $libraries, ['signUpDuration', 'shipsPerDay', 'books']);

$analyzer->analyze();

