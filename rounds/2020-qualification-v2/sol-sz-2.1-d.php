<?php

/**
 * Record: 5.063.890
 * Total books: 77.906
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

$alreadyScannedBooksToClean = [];

for ($i = 0; $i < $countDays; $i++) {
    if ($signupRemainingDays == 0) {
        if ($libraryUnderSignup) {
            $librariesUnderScan[] = $libraryUnderSignup;
        }

        $libraryUnderSignup = getStonksLibrary($libraries, $alreadyScannedBooksToClean);
        $signupRemainingDays = $libraryUnderSignup->signUpDuration;
    }

    /** @var Book $book */
    foreach ($librariesUnderScan as $index => $library) {
        for ($j = 0; $j < $library->shipsPerDay; $j++) {
            $book = $library->books->pop();
            if (!in_array($book->id, $alreadyScannedBooksToClean)) {
                $totalBook++;
                $totalAward += $book->award;
                $alreadyScannedBooksToClean[] = $book->id;
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

Log::out('Total books: ' . $totalBook);

function getStonksLibrary($libraries, &$alreadyScannedBooksToClean)
{
    $maxLibrary = null;

    /** @var Collection $libraries */
    /** @var Library $library */
    foreach ($libraries as $library) {
        $inLibrariesSum = 0;

        /** @var Book $book */
        foreach ($library->books as $book) {
            if (in_array($book->id, $alreadyScannedBooksToClean)) {
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

    $alreadyScannedBooksToClean = [];
    return $libraries->pull($maxLibrary->id);
}
