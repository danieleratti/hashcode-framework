<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'c';

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

    /** @var Library $library */
    $library = $libraries[$libraryId];
    if ($library) {
        $takeDays = $countDays - $currentDay - $library->signUpDuration;
        if ($takeDays > 0) {
            $booksChunked = $library->books
                ->sortByDesc('award')
                ->chunk($library->shipsPerDay)
                ->take($takeDays);

            /*
            if($library->id == 1) {
                Log::out("booksChunked->count() = " . $booksChunked->count(), 'red');
            }*/

            $booksChunkedScore = $booksChunked->reduce(function ($carry, $books) {
                return $carry + $books->sum('award');
            }, 0);
            $booksChunkedScoreTail = $booksChunked->take(-round($avgSignupDuration))->reduce(function ($carry, $books) {
                return $carry + $books->sum('award');
            }, 0);
            $library->booksChunked = $booksChunked;
            //$library->booksChunkedScore = pow($booksChunkedScore, 1.5) / pow(10 * $library->signUpDuration / $avgSignupDuration, 0.75); //NEW FAKE SCORE!!!
            //$library->booksChunkedScore = pow($booksChunkedScore, 1) / pow(1 + $library->signUpDuration / $avgSignupDuration, 1) / pow($booksChunkedScoreTail * 0.3, 1); //NEW FAKE SCORE!!!
            $library->booksChunkedScore = pow($booksChunkedScore, 1) / pow($booksChunkedScoreTail * 0.3, 1); //NEW FAKE SCORE!!!
        } else {
            $library->booksChunked = collect();
            $library->booksChunkedScore = 0;
        }
    }
}

function alignLibraries($cutDays)
{
    global $libraries, $currentDay, $countDays;
    foreach ($libraries as $library) {
        /** @var Library $library */
        if ($countDays - $currentDay - $library->signUpDuration >= $cutDays) {
            /*
            $outChunks = $library->booksChunked->splice($library->booksChunked->count() - $cutDays); // prendo gli ultimi
            $outChunksScore = $outChunks->reduce(function ($carry, $books) {
                return $carry + $books->sum('award');
            }, 0);
            $library->booksChunkedScore -= $outChunksScore;*/
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
        fullAlignLibrary($libraryId);
    }
}

function takeLibrary($library)
{
    global $outputLibraries, $libraries, $currentDay, $score;

    /** @var Library $library */
    $takenBooks = $library->booksChunked->collapse();

    $outputLibraries[] = [
        'libraryId' => $library->id,
        //'books' => $library->books->pluck('id')->toArray()
        'books' => $takenBooks->pluck('id')->toArray()
    ];

    $score += $takenBooks->sum('award');

    foreach ($library->books as $book) {
        /** @var Book $book */
        $book->inLibraries->forget($library->id);
    }

    $libraries->forget($library->id);
    alignLibraries($library->signUpDuration);
    purgeBooksFromLibraries($takenBooks->pluck('id')->toArray());

    $currentDay += $library->signUpDuration;
}

/// alignLibraries($cutD) che taglia la coda di tutte le libraries rimanenti e ridà il nuovo punteggio temporaneo
/// takeLibrary($libraryId) prende tutti i books rimanenti nella library [d|signupDuration|<tempo utile chunkato>]=D, li mette nell'output e chiama la fx purgeBooksFromLibraries + cancellare library
/// purgeBooksFromLibraries($bookIds)

/// TMP solo per quello score: Per ogni library, do un tmpscore a ogni libro pari a $book->awardLibro/count($book2library[$bookId])
/// TMP solo per quello score: Sempre dentro al ciclo sopra -> Ordino questi libri desc per tmpscore e prendo i primi $library->shipsPerDay*($D-$library->signupDuration)
/// Light scoring per filtrare le prime libraries ordinate per il totale del tmpscore, e tagliare fuori quelle che non ci stanno nella duration D + padding variabile (%)
/// Butto via libraries non interessanti (fuori D+padding)
/// Sistemo i $book2library con le sole tagliate
///
/// START (heating): per ogni library rimanente: fullAlignLibrary($libraryId)
///
/// WHILE d < D:
/// prendo la prima library sortByDesc(totalRemainingScore)
/// takeLibrary($first->libraryId)

$avgSignupDuration = $libraries->avg('signUpDuration');

Log::out('fullAlignLibraries');
foreach ($libraries as $library)
    fullAlignLibrary($library->id);

Log::out('algo...');
while ($firstLibrary = $libraries->sortByDesc('booksChunkedScore')->first()) {
    Log::out('dStart = ' . $currentDay . '/' . $countDays);
    Log::out('taken library ' . $firstLibrary->id . ' on ' . $libraries->count() . ' libraries');
    takeLibrary($firstLibrary);
    Log::out('dEnd = ' . $currentDay . '/' . $countDays);
    Log::out('remaining Libraries = ' . count($libraries));
    Log::out('SCORE(' . $fileName . ') = ' . $score, 'red');
}

// output
$output = [];
$output[] = count($outputLibraries);
foreach ($outputLibraries as $l) {
    $output[] = $l['libraryId'] . " " . count($l['books']);
    $output[] = implode(" ", $l['books']);
}

$fileManager->output(implode("\n", $output));
