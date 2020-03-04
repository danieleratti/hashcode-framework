<?php

$fileName = 'a';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/**
 * @var int $countBooks
 * @var int $countLibraries
 * @var int $countDays
 * @var Book[] $books
 * @var Library[] $libraries
 */

/** @var int $totalScore */
$totalScore = 0;
/** @var Book[] $scannedBooks */
$scannedBooks = [];
/** @var Book[] $notScannedBooks */
$notScannedBooks = $books;
/** @var Library[] $orderedSignuppedLibraries */
$orderedSignuppedLibraries = [];
/** @var Library[] $signuppedLibraries */
$signuppedLibraries = [];
/** @var Library[] $notSignuppedLibraries */
$notSignuppedLibraries = $libraries;
/** @var Library $currentSignupLibrary */
$currentSignupLibrary = null;

// Algo

foreach ($books as $b) {
    $b->rAward = $b->award / pow(count($b->inLibraries), 1);
}
foreach ($libraries as $l) {
    foreach ($l->books as $b) {
        $l->rCurrentTotalAward += $b->rAward;
    }
}

$analyzer = new Analyzer($fileName, [
    'books_count' => count($books),
    'libraries_count' => count($libraries),
    'max_days' => $countDays,
]);
$analyzer->addDataset('books', $books, ['award', 'inLibraries']);
$analyzer->addDataset('libraries', $libraries, ['signUpDuration', 'shipsPerDay', 'books']);
$analyzer->analyze();
