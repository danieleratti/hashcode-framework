<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'c';

include 'reader-ss.php';

/** @var Collection $libraries */
$libraries = assignScoreToLibraries($libraries);

const DAYS = 100000;

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

for ($i = 0; $i < DAYS; $i++) {
    if ($signupRemainingDays == 0) {
        if ($libraryUnderSignup) {
            $librariesUnderScan[] = $libraryUnderSignup;
        }

        do {
            $libraryUnderSignup = $libraries->pop();
        } while ($libraryUnderSignup->signUpDuration >= (DAYS - $i));
        $signupRemainingDays = $libraryUnderSignup->signUpDuration;
    }

    /** @var Book $book */
    foreach ($librariesUnderScan as $index => $library) {
        for ($j = 0; $j < $library->shipsPerDay; $j++) {
            $book = $library->books->pop();
            $needRerun = true;
            if (!in_array($book->id, $alreadyScannedBooks)) {
                $totalBook++;
                $totalAward += $book->award;
                $alreadyScannedBooks[] = $book->id;
                $alreadyScannedBooksToClean[] = $book->id;
            }

            if (count($library->books) == 0) {
                $libraries->forget($library->id);
                unset($librariesUnderScan[$index]);
                break;
            }
        }
    }


    if ($needRerun) {
        foreach ($libraries as $library) {
            /** @var Library $library */
            $library->books = $library->books->filter(function ($b) use ($alreadyScannedBooksToClean) {
                return !in_array($b->id, $alreadyScannedBooksToClean);
            });
        }

        $libraries = assignScoreToLibraries($libraries);
        $alreadyScannedBooksToClean = [];
        $needRerun = false;
    }
    $signupRemainingDays--;

    Log::out('Day ' . ($i + 1));
    Log::out('Total award: ' . $totalAward, 1);

}

Log::out('Total books: ' . $totalBook);

function assignScoreToLibraries($libraries)
{
    /** @var Collection $libraries */
    /** @var Library $library */
    foreach ($libraries as $library) {
        $libraryAward = 0;

        /** @var Book $book */
        foreach ($library->books as $book) {
            $libraryAward += $book->award;
        }
        $library->score = $libraryAward / $library->signUpDuration;
    }

    return $libraries->sortBy('score');
}
