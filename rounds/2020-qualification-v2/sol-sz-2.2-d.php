<?php

/**
 * Record: 5.039.450
 * Total books: 77.530
 */

use Utils\Collection;
use Utils\Log;

$fileName = 'd';

include 'reader-ss.php';

/** @var Collection $libraries */

$signupRemainingDays = 0;
/** @var Library $libraryUnderSignup */
$libraryUnderSignup = null;
/** @var Library[] $librariesUnderScan */
$librariesUnderScan = [];
$totalBook = 0;
$totalAward = 0;
$needRerun = false;

$alreadyScannedBooks = [];

for ($i = 0; $i < $countDays; $i++) {
    if ($signupRemainingDays == 0) {
        if ($libraryUnderSignup) {
            $librariesUnderScan[] = $libraryUnderSignup;
        }

        $libraryUnderSignup = getStonksLibrary($libraries, $alreadyScannedBooks);
        foreach ($libraryUnderSignup->books as $book) {
            $alreadyScannedBooks[$book->id] = $libraryUnderSignup->id;
        }
        $signupRemainingDays = $libraryUnderSignup->signUpDuration;
    }

    /** @var Book $book */
    foreach ($librariesUnderScan as $index => $library) {
        for ($j = 0; $j < $library->shipsPerDay; $j++) {
            $book = $library->books->pop();
            // TODO: È necessario che non ci siano libri duplicati nella stessa libreria. Forse è così.
            if ($alreadyScannedBooks[$book->id] === $library->id) {
                $totalBook++;
                $totalAward += $book->award;
            }

            if (count($library->books) == 0) {
                $libraries->forget($library->id);
                unset($librariesUnderScan[$index]);
                break;
            }
        }
    }

    $signupRemainingDays--;

    Log::out('Day ' . ($i + 1));
    Log::out('Actual award: ' . $totalAward, 1);
    Log::out('Estimated award: ' . floor(($totalAward / ($i + 1)) * ($countDays)), 1);
}


function getStonksLibrary($libraries, $alreadyScannedBooks)
{
    $maxLibrary = null;

    /** @var Collection $libraries */
    /** @var Library $library */
    foreach ($libraries as $library) {
        $inLibrariesSum = 0;

        /** @var Book $book */
        foreach ($library->books as $book) {
            if (array_key_exists($book->id, $alreadyScannedBooks) && $alreadyScannedBooks[$book->id] !== $library->id) {
                $library->books->forget($book->id);
                continue;
            }
            $inLibrariesSum += count($book->inLibraries);
        }

        $nBooks = count($library->books);

        if ($nBooks === 0) {
            $libraries->forget($library->id);
            continue;
        }

        $library->score = $nBooks / ($inLibrariesSum / $nBooks);

        if ($maxLibrary === null || $library->score > $maxLibrary->score) {
            $maxLibrary = $library;
        }
    }

    return $libraries->pull($maxLibrary->id);
}
