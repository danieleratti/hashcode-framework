<?php

use Utils\Log;
use Utils\Serializer;

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
    foreach ($library->books as $book) {
        $SCORE += $book->award;
        // deindexing books from libraries
        foreach ($book->inLibraries as $_library) {
            $_library->books->forget($book->id);
        }
        $takenBooks->put($book->id, $book);
        $books->forget($book->id);
    }
}

function isBookTaken(Book $book, Library $excludedLibrary = null)
{
    /** @var Library $library */
    foreach ($book->inLibraries as $library) {
        if ($library->taken && (!$excludedLibrary || $library->id != $excludedLibrary->id))
            return true;
    }
    return false;
}

function switchLibrary(Library $libraryIN, Library $libraryOUT = null)
{
    global $SCORE, $T, $libraries, $books, $takenLibraries;
    Log::out("Switch library {$libraryIN->id} <-> " . ($libraryOUT ? $libraryOUT->id : "(New Insert)") . "", 1, "blue");
    if ($libraryIN->taken) Log::error("LibraryIN already taken!");
    if ($libraryOUT && !$libraryOUT->taken) Log::error("LibraryOUT not yet taken!");

    $libraryIN->taken = true;
    if ($libraryOUT)
        $libraryOUT->taken = false;
    else
        $T += $libraryOUT->signUpDuration;

    if ($libraryOUT) {
        foreach ($libraryOUT->books as $book) {
            if ($book->taken && !isBookTaken($book)) {
                $book->taken = false;
                $SCORE -= $book->award;
            }
        }
    }

    foreach ($libraryIN->books as $book) {
        if (!$book->taken) {
            $book->taken = true;
            $SCORE += $book->award;
        }
    }
}

function calculateSwitch(Library $libraryIN, Library $libraryOUT)
{
    global $books;
    $intersectedBooks = $libraryIN->books->pluck('id')->intersect($libraryOUT->books->pluck('id'));
    $removedBooks = $libraryOUT->books->pluck('id')->diff($intersectedBooks);
    $addedBooks = $libraryIN->books->pluck('id')->diff($intersectedBooks);
    $deltaScore = $addedBooks->reduce(function ($carry, $book) use ($books) {
        return $carry + ($books[$book]->taken ? 0 : $books[$book]->award);
    }, 0);
    foreach ($removedBooks as $book) {
        if (!isBookTaken($books[$book], $libraryOUT))
            $deltaScore -= $books[$book]->award;
    }
    return $deltaScore;
}

function findBestSwitch(Library $libraryOUT)
{
    $bestDeltaScore = -1;
    $bestLibrary = null;
    foreach ($libraryOUT->books as $book) {
        foreach ($book->inLibraries as $library) {
            if (!$library->taken) {
                $score = calculateSwitch($library, $libraryOUT);
                if ($score > $bestDeltaScore) {
                    $bestDeltaScore = $score;
                    $bestLibrary = $library;
                }
            }
        }
    }

    if ($bestDeltaScore <= -1)
        return null;

    return [
        "deltaScore" => $bestDeltaScore,
        "library" => $bestLibrary
    ];
}

$serializeMethod = "json";
//Serializer::clean('topo-sol-d-seeder', $serializeMethod);
if (!($flatDb = Serializer::get('topo-sol-d-seeder', $serializeMethod))) {

    while ($T < $countDays) {
        $librariesPerCycle = 1;

        // Sort libraries by score
        $sortedLibraries = $libraries->sortByDesc(function (Library $l) {
            return $l->books->reduce(function ($carry, Book $book) {
                return $carry + $book->award / pow($book->inLibraries->count(), 1);
            }, 0);
        });

        foreach ($sortedLibraries->slice(0, $librariesPerCycle) as $sortedLibrary) {
            if ($T < $countDays) {
                takeLibrary($sortedLibrary);
                Log::out("day $T/$countDays) SCORE = $SCORE");
            }
        }
    }

    Serializer::set('topo-sol-d-seeder', [
        "takenLibraries" => $takenLibraries->pluck("id")->toArray(),
    ], $serializeMethod);
    die("Restart the algo!");
}

$takenLibrariesIds = $flatDb["takenLibraries"];
foreach ($takenLibrariesIds as $libraryId)
    switchLibrary($libraries[$libraryId]);
Log::out("day $T/$countDays) SCORE = $SCORE");

$macroiteration = 0;
while(true) {
    $macroiteration++;
    foreach ($libraries->where('taken', true) as $library) {
        if ($library->taken) {
            $bestSwitch = findBestSwitch($library);
            if ($bestSwitch) {
                Log::out("[$macroiteration] Switch for gaining {$bestSwitch['deltaScore']}", 1, 'purple');
                switchLibrary($bestSwitch['library'], $library);
                Log::out("SCORE = $SCORE");
            }
        }
    }
}

