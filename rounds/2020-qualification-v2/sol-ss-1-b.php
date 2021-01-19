<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'b';

include 'reader-ss.php';

/** @var Collection $libraries */
$libraries = $libraries->sortByDesc('signUpDuration');

$signupRemainingDays = 0;
$libraryUnderSignup = null;
$librariesUnderScan = [];
$totalAward = 0;

for ($i = 0; $i < 1000; $i++) {
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
        $book = $library->books->pop();
        $totalAward += $book->award;

        if (count($library->books) == 0) {
            unset($librariesUnderScan[$index]);
        }
    }

    $signupRemainingDays--;

    Log::out('Day ' . ($i + 1));
    Log::out('Total award: ' . $totalAward, 1);

}
