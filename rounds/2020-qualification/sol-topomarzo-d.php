<?php

/**
 * ONLY FOR D!
 */

use Utils\Cerberus;
use Utils\Collection;
use Utils\Log;

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

/// VAR:
$score = 0;
$currentDay = 0;
$outputLibraries = [];

/// FUNZIONI:
/// fullAlignLibrary($libraryId) rifà l'allineamento totale
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

            $uniqueness = 0;
            $uniqueBooksCutScore = 0;
            foreach ($booksCut as $b) {
                if ($b->inLibraries->count() == 1) {
                    $uniqueness += 1;
                    $uniqueBooksCutScore += $b->award;
                }
            }
            $uniqueness /= count($booksCut);

            $booksCutScore = $booksCut->sum('award');

            $library->booksCut = $booksCut;
            //$library->booksCutScore = $uniqueness; //$booksCutScore; //+ contare la uniqueness //+ contare la count($Books)

            switch ($algo) {
                case 1:
                    $library->booksCutScore = $uniqueness; // solo unicità: più libri unici = meglio
                    break;
                case 2:
                    $library->booksCutScore = $uniqueness * $booksCutScore; // unicità x award
                    break;
                case 3:
                    $library->booksCutScore = $uniqueness * pow($booksCutScore, 2); // unicità x award pesato di più
                    break;
                case 4:
                    $library->booksCutScore = $uniqueness * pow($booksCutScore, 0.5); // unicità x award pesato di meno
                    break;
                case 5:
                    $library->booksCutScore = $uniqueness * count($booksCut); // unicità x num libri (= num libri unici)
                    break;
                case 6:
                    $library->booksCutScore = $uniqueBooksCutScore; // score dei libri unici
                    break;
                case 7:
                    $library->booksCutScore = $uniqueBooksCutScore * count($booksCut); // score libri unici * count libri
                    break;
                case 8:
                    $library->booksCutScore = $uniqueness * $uniqueBooksCutScore * count($booksCut); // unicità * score libri unici * count libri
                    break;
                case 9:
                    $library->booksCutScore = pow($uniqueBooksCutScore, 1.2) * pow($booksCutScore, 1.0); // score unici pesato * score totale cut pesato meno
                    break;
                case 10:
                    $library->booksCutScore = pow($uniqueBooksCutScore, 2) * pow($booksCutScore, 1.0); // score unici pesato * score totale cut pesato meno
                    break;
            }

        } else {
            $library->booksCut = collect();
            $library->booksCutScore = 0;
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

$avgSignupDuration = $libraries->avg('signUpDuration');

Log::out('fullAlignLibraries');
foreach ($libraries as $library)
    fullAlignLibrary($library->id);

Log::out('algo...');
while ($firstLibrary = $libraries->sortByDesc('booksCutScore')->first()) {
    //Log::out('dStart = ' . $currentDay . '/' . $countDays);
    //Log::out('taken library ' . $firstLibrary->id . ' on ' . $libraries->count() . ' libraries');
    takeLibrary($firstLibrary);
    //Log::out('dEnd = ' . $currentDay . '/' . $countDays);
    //Log::out('remaining Libraries = ' . count($libraries));
    Log::out('DAY=' . $currentDay . '/' . $countDays . ' // SCORE(' . $fileName . ') = ' . $score . ' [ESTSCORE=' . round($countDays / $currentDay * $score) . ']', 0, 'red');
}

// output
$output = [];
$output[] = count($outputLibraries);
foreach ($outputLibraries as $l) {
    $output[] = $l['libraryId'] . " " . count($l['books']);
    $output[] = implode(" ", $l['books']);
}

$fileManager->output(implode("\n", $output), '-algo' . $algo);
