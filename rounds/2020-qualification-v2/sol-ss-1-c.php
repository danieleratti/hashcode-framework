<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'c';

include 'reader-ss.php';

/** @var Collection $libraries */
$libraries = assignScoreToLibraries($libraries);

$signupRemainingDays = 0;
$libraryUnderSignup = null;
$librariesUnderScan = [];
$totalBook = 0;
$totalAward = 0;

$alreadyScannedBooks = [];
$alreadyScannedBooksToClean = [];

for ($i = 0; $i < 100000; $i++) {
    if ($signupRemainingDays == 0) {
        if ($libraryUnderSignup) {
            $librariesUnderScan[] = $libraryUnderSignup;
        }

        $libraryUnderSignup = $libraries->pop();
        $signupRemainingDays = $libraryUnderSignup->signUpDuration;
    }

    /** @var Book $book */
    /** @var Library $library */
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
                unset($librariesUnderScan[$index]);
                break;
            }
        }
    }


    if($needRerun) {
        foreach ($libraries as $library) {
            /** @var Library $library */
            $library = $library->books->filter(function ($b) use ($alreadyScannedBooksToClean) {
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
