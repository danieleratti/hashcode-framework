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
$takenBooks = collect();
function takeLibrary(Library $library)
{
    global $SCORE, $T, $libraries, $books, $takenLibraries, $takenBooks;
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
        $takenBooks->put($book->id, $book);
        $books->forget($book->id);
    }
}

while($T < $countDays) {
    $librariesPerCycle = 1;

    // Sort libraries by score
    $sortedLibraries = $libraries->sortByDesc(function (Library $l) {
        return $l->books->reduce(function ($carry, Book $book) {
            return $carry + $book->award / pow($book->inLibraries->count(), 1);
        }, 0);
    });

    foreach($sortedLibraries->slice(0, $librariesPerCycle) as $sortedLibrary) {
        if($T < $countDays) {
            takeLibrary($sortedLibrary);
            Log::out("day $T/$countDays) SCORE = $SCORE");
        }
    }
}

// TODO: Sort takenLibraries affinché le ultime abbiano meno libri e fare output!

// 5014880 senza potenza chunk 5
// 5039385 run php senza potenza chunk 1


// IDEA:
// snapshotto il risultato dell'algo eseguito al meglio, ovvero:
//  la lista delle $takenLibraries
//  la lista delle $libraries rimanenti
//  la lista dei $books rimanenti
//  li uso come seme dell'algoritmo a supporto topo-sol-d-seeded.php ($seededTakenLibraries, $seededTakenBooks)


// TODO: Valutare test con MySQL
