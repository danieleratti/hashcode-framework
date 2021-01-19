<?php

use Utils\Collection;
use Utils\Log;
use Utils\Analysis\Analyzer;

$fileName = 'd';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var Book[] $books */
/** @var Library[] $libraries */

$SCORE = 0;
$T = 0;
$takenLibraries = collect();
function takeLibrary(Library $library)
{
    global $SCORE, $T, $libraries, $books, $takenLibraries;
    Log::out("Take library {$library->id}", 1, "blue");
    $takenLibraries->put($library->id, $library);
    $libraries->forget($library->id);
    $T += $library->signUpDuration;
    foreach($library->books as $book) {
        $SCORE += $book->award;
        // deindexing books from libraries
        foreach($book->inLibraries as $_library) {
            $_library->books->forget($book->id);
        }
        $books->forget($book->id);
    }
}

while($T < $countDays) {
    $librariesPerCycle = 10;

    // Sort libraries by score
    $sortedLibraries = $libraries->sortByDesc(function (Library $l) {
        return $l->books->reduce(function ($carry, Book $book) {
            return $carry + $book->award / $book->inLibraries->count();
        }, 0);
    });

    foreach($sortedLibraries->slice(0, $librariesPerCycle) as $sortedLibrary) {
        takeLibrary($sortedLibrary);
        Log::out("day $T/$countDays) SCORE = $SCORE");
    }
}

// TODO: Sort takenLibraries affinché le ultime abbiano meno libri e fare output!

