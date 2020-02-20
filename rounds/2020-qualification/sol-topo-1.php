<?php

use Utils\Collection;

$fileName = 'a';

include 'reader.php';

/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */

/// VAR:
$currentDay = 0;

/// FUNZIONI:
/// fullAlignLibrary($libraryId) rifà l'allineamento totale
function fullAlignLibrary($libraryId)
{
    global $countDays;
    global $currentDay;
    global $libraries;
    /** @var Library $library */
    $library = $libraries[$libraryId];
    $takeDays = $countDays - $currentDay - $library->signUpDuration;
    if ($takeDays > 0) {
        $booksChunked = $library->books
            ->sortByDesc('award')
            ->chunk($library->shipsPerDay)
            ->take($takeDays);
        $booksChunkedScore = $booksChunked->reduce(function ($carry, $books) {
            return $carry + $books->sum('award');
        }, 0);
        $library->booksChunked = $booksChunked;
        $library->booksChunkedScore = $booksChunkedScore;
    } else {
        $library->booksChunked = collect();
        $library->booksChunkedScore = 0;
    }
}

function alignLibraries($cutDays)
{
    global $libraries;
    foreach ($libraries as $library) {
        /** @var Library $library */
        if ($library->booksChunked->count() > $cutDays) {
            $outChunks = $library->booksChunked->splice($cutDays); // prendo gli ultimi
            $outChunksScore = $outChunks->reduce(function ($carry, $books) {
                return $carry + $books->sum('award');
            }, 0);
            $library->booksChunkedScore -= $outChunksScore;
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

$countDays = 4; // 1 gg + signup
fullAlignLibrary(0);
fullAlignLibrary(1);
//alignLibraries(1);
echo "ciao";
purgeBooksFromLibraries([4]);
echo "ciao";

//$fileManager->output(implode("\n", $output));
