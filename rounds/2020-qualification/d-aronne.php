<?php

use Utils\Collection;

$fileName = 'd';

include 'reader.php';

/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */


function chooseBestLibrary(Library $libraryA, Library $libraryB)
{
    $scoreA = $libraryA->books->count();
    $scoreB = $libraryB->books->count();

    return $scoreA >= $scoreB ? $libraryA : $libraryB;
}

function scanBooks(Library $library, $remainingDays)
{
    $result = [];
    $books = $library->books;
    for ($d = 0; $d < $remainingDays; $d++) {
        if (!$books->count())
            break;

        for ($i = 0; $i < $library->shipsPerDay; $i++) {
            $result[] = $books->shift()->id;
        }
    }

    return $result;
}

$filteredLibraries = [];
$rawLibraries = $libraries->toArray();
for ($i = 0; $i < $countLibraries - 1; $i += 2) {
    /**
     * @var Library $firstLibrary
     * @var Library $secondLibrary
     */
    $firstLibrary = $rawLibraries[$i];
    $secondLibrary = $rawLibraries[$i + 1];

    if ($firstLibrary->books->first()->id != $secondLibrary->books->first()->id)
        die('Scemo');

    $filteredLibraries[] = chooseBestLibrary($firstLibrary, $secondLibrary);
}

$filteredLibraries = collect($filteredLibraries);
$filteredLibraries = $filteredLibraries->sortByDesc('booksNumber');

$output = [];
$day = 0;
$signuppedLibraries = 0;
/** @var Library $library */
foreach ($filteredLibraries as $library) {
    echo 'D: ' . $day . ' --> Scanning library: ' . $library->id . PHP_EOL;
    $setupFinishedAt = $day + $library->signUpDuration;
    $remainingDays = $countDays - $setupFinishedAt;

    if ($remainingDays <= 0) {
        echo '    ...not enought days for scanning, continue' . PHP_EOL;
        continue;
    }

    $scannedBooks = scanBooks($library, $remainingDays);
    $output[] = $library->id . ' ' . count($scannedBooks);
    $output[] = implode(' ', $scannedBooks);
    $signuppedLibraries++;

    $day = $setupFinishedAt;
}

array_unshift($output, $signuppedLibraries);
$fileManager->output(implode("\n", $output));
