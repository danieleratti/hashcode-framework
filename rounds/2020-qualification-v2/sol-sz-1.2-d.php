<?php

/**
 * Record: 5.036.460
 * Total books: 77.484
 */

use Utils\Collection;
use Utils\Log;

$fileName = 'd';

include 'reader-ss.php';

/** @var Collection $libraries */
$libraries = assignScoreToLibraries($libraries, []);

$signupRemainingDays = 0;
/** @var Library $libraryUnderSignup */
$libraryUnderSignup = null;
/** @var Library[] $librariesUnderScan */
$librariesUnderScan = [];
$totalBook = 0;
$totalAward = 0;
$needRerun = false;

$alreadyScannedBooks = [];
$alreadyScannedBooksToClean = [];

for ($i = 0; $i < $countDays; $i++) {
    if ($signupRemainingDays == 0) {
        if ($libraryUnderSignup) {
            $librariesUnderScan[] = $libraryUnderSignup;
        }

        do {
            $libraryUnderSignup = $libraries->pop();
        } while ($libraryUnderSignup->signUpDuration >= ($countDays - $i));
        $signupRemainingDays = $libraryUnderSignup->signUpDuration;
    }

    /** @var Book $book */
    foreach ($librariesUnderScan as $index => $library) {
        for ($j = 0; $j < $library->shipsPerDay; $j++) {
            $book = $library->books->pop();
            if (!in_array($book->id, $alreadyScannedBooks)) {
                $totalBook++;
                $totalAward += $book->award;
                $alreadyScannedBooks[] = $book->id;
                $alreadyScannedBooksToClean[] = $book->id;
                $needRerun = true;
            }

            if (count($library->books) == 0) {
                $libraries->forget($library->id);
                unset($librariesUnderScan[$index]);
                break;
            }
        }
    }


    if ($needRerun) {
//        foreach ($libraries as $library) {
//            /** @var Library $library */
//            $library->books = $library->books->filter(function ($b) use ($alreadyScannedBooksToClean) {
//                return !in_array($b->id, $alreadyScannedBooksToClean);
//            });
//        }

        $libraries = assignScoreToLibraries($libraries, $alreadyScannedBooksToClean);
        $alreadyScannedBooksToClean = [];
        $needRerun = false;
    }
    $signupRemainingDays--;

    Log::out('Day ' . ($i + 1));
    Log::out('Actual award: ' . $totalAward, 1);
    Log::out('Estimated award: ' . floor(($totalAward / ($i + 1)) * ($countDays)), 1);
}

Log::out('Total books: ' . $totalBook);

function assignScoreToLibraries($libraries, $alreadyScannedBooksToClean)
{
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

        if ($nBooks == 0) {
            $libraries->forget($library->id);
            continue;
        }

        $library->score = $nBooks / ($inLibrariesSum / $nBooks);
    }

    return $libraries->sortBy('score');
}
