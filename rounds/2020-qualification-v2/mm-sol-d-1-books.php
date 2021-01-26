<?php

$fileName = 'd';

include './reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var Book[] $books */
/** @var Library[] $libraries */
/** @var int $countDays */

$SCORE = 0;
$takenLibraries = collect();

function takeLibrary(Library $library, int $t)
{
    global $SCORE, $libraries, $books, $takenLibraries;
    $takenLibraries->put($library->id, $library);
    $libraries->forget($library->id);
    foreach ($library->books as $book) {
        $SCORE += $book->award;
        // deindexing books from libraries
        foreach ($book->inLibraries as $_library) {
            $_library->books->forget($book->id);
        }
        $books->forget($book->id);
    }
}


// ALGO

$booksFiltered = $books->filter(fn(Book $b) => $b->inLibraries->count() === 3);
$filteredLibraries = collect();
foreach ($booksFiltered as $b) {
    /** @var Book $b */
    foreach ($b->inLibraries as $lk => $l)
        $filteredLibraries->put($lk, $l);
}

echo "C: " . $filteredLibraries->count();
die();

$t = 0;
while ($t < $countDays) {
    echo "$t\n";

    $libraries = $libraries->sortByDesc(
        fn(Library $l) => $l->books->count() * 1000 + $l->books->filter(fn(Book $b) => $b->inLibraries->count() == 3)->count()
    );
    /** @var Library $takenLibrary */
    $takenLibrary = $libraries->first();
    if ($takenLibrary) {
        takeLibrary($takenLibrary, $t);
        $t += $takenLibrary->signUpDuration;
    }

    /*
    foreach ($takenLibraries as $l) {
        /** @var Library $l *
        $booksToTake = $l->books->sortByDesc(fn(Book $b) => $b->inLibraries->count())->take($l->shipsPerDay);
    }
    */
}

echo "Finito con score $SCORE.";
