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

//$books->filter(fn(Book $b) => $b->inLibraries->count() === 3)->avg()

echo "O3";

foreach ($libraries as $l) {
    /** @var Library $l */
    //$l->occAward = $l->books->countBy(fn(Book $b) => $b->inLibraries->count() == 2);
    $l->occAward = $l->books->filter(fn(Book $b) => $b->inLibraries->count() == 2)->count();
}

$t = 0;
while ($t < $countDays) {
    echo "$t\n";

    $libraries = $libraries->sortByDesc(
        fn(Library $l) => $l->books->count() * 1000 + $l->occAward
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
