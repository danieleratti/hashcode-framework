<?php

/**
 * ONLY FOR D!
 */

use Utils\Cerberus;
use Utils\Collection;
use Utils\Log;
use Utils\Serializer;

require_once '../../bootstrap.php';
$fileName = 'd';
$algo = 1;
Cerberus::runClient(['algo' => 1]);

include 'reader.php';

/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */

function fullAlignLibrary($libraryId)
{
    global $countDays;
    global $currentDay;
    global $libraries;
    global $avgSignupDuration;
    global $algo;

    /** @var Library $library */
    $library = $libraries[$libraryId];
    if ($library) {
        $takeDays = $countDays - $currentDay - $library->signUpDuration;
        if ($takeDays > 0) {
            $booksCut = $library->books
                ->sortBy('inLibrariesCount')
                ->sortByDesc('award')
                ->take($takeDays);
            $library->booksCut = $booksCut;
            $library->booksCutCount = $booksCut->count();
            $library->booksCutScore = $booksCut->sum('award'); //TODO ALGO: qui basarsi anche sul count!
        } else {
            $library->booksCut = collect();
            $library->booksCutScore = 0;
            $library->booksCutCount = 0;
        }
    }
}

function alignLibraries()
{
    global $libraries, $currentDay, $countDays;
    foreach ($libraries as $library) {
        /** @var Library $library */
        if ($countDays - $currentDay - $library->signUpDuration >= 0) {
            fullAlignLibrary($library->id); //TMP
        } else {
            $libraries->forget($library->id);
        }
    }
}

function purgeBooksFromLibraries($bookIds)
{
    global $books;
    $dirtyLibraryIds = [];
    foreach ($books->whereIn('id', $bookIds) as $book) {
        /** @var Book $book */
        foreach ($book->inLibraries as $libraryId => $library)
            $dirtyLibraryIds[$libraryId] = $library;
    }

    foreach ($dirtyLibraryIds as $libraryId => $library) {
        // ELIMINO TUTTI STI LIBRI DI MERDA!!!!!
        foreach ($bookIds as $bookId)
            $library->books->forget($bookId);
        //fullAlignLibrary($libraryId);
    }
}

function takeLibrary($library)
{
    global $outputLibraries, $libraries, $currentDay, $score, $countDays;

    /** @var Library $library */
    $dirtyLibs = [];
    $takenBooks = $library->booksCut;

    $outputLibraries[] = [
        'libraryId' => $library->id,
        'books' => $takenBooks->pluck('id')->toArray()
    ];

    $score += $takenBooks->sum('award');

    foreach ($library->books as $book) {
        /** @var Book $book */
        $takenBooks[$book->id] = true;
        $book->inLibraries->forget($library->id);
        foreach ($book->inLibraries as $lib) {
            $dirtyLibs[$lib->id] = $lib;
        }
    }

    $libraries->forget($library->id);
    purgeBooksFromLibraries($takenBooks->pluck('id')->toArray());
    $currentDay += $library->signUpDuration;

    if ($countDays - $currentDay > 14 + 2) { // 14 is the max number of books (1 book = 1 day) and 2 is the signup duration
        foreach ($dirtyLibs as $lib)
            fullAlignLibrary($lib->id);
    } else {
        alignLibraries();
    }
}

// prepare
$takenBooks = [];

// snapshot algo 1
$countDays = 5;

// prepare
Log::out('fullAlignLibraries');
foreach ($libraries as $library)
    fullAlignLibrary($library->id);

// algo 1
Log::out('algo...');
foreach ($books->sortByDesc('award')->sortBy('inLibrariesCount') as $book) {
    if (!$takenBooks[$book->id]) {

        $firstLibrary = $book->inLibraries->sortByDesc('booksCutCount')->first();

        if ($firstLibrary->booksCutScore > 0) {
            takeLibrary($firstLibrary);
            Log::out('DAY=' . $currentDay . '/' . $countDays . ' // SCORE(' . $fileName . ') = ' . $score . ' [ESTSCORE=' . round($countDays / $currentDay * $score) . ']', 0, 'red');
        } else {
            if ($currentDay >= $countDays - 3) break;
        }
        if ($currentDay >= $countDays) break;
    }
}

// algo 2
Log::out('starting algo 2 with score=' . $score . '...');


// output
$output = [];
$output[] = count($outputLibraries);
foreach ($outputLibraries as $l) {
    $output[] = $l['libraryId'] . " " . count($l['books']);
    $output[] = implode(" ", $l['books']);
}

$fileManager->output(implode("\n", $output), '-algo' . $algo);

