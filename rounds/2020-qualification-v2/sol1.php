<?php

use Utils\Collection;
use Utils\Log;
use Utils\Analysis\Analyzer;

$fileName = 'a';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var Book[] $books */
/** @var Library[] $libraries */

$SCORE = 0;
$takenLibraries = collect();
function takeLibrary(Library $library)
{
    global $SCORE, $libraries, $books, $takenLibraries;
    $takenLibraries->put($library->id, $library);
    $libraries->forget($library->id);
    foreach($library->books as $book) {
        $SCORE += $book->award;
        // deindexing books from libraries
        foreach($book->inLibraries as $_library) {
            $_library->books->forget($book->id);
        }
        $books->forget($book->id);
    }
}


