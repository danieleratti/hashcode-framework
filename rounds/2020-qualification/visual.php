<?php

use Utils\Chart;
use Utils\Collection;

$fileName = 'b';

include 'reader.php';

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
            $library->booksChunkedScore = $booksChunkedScore;
            //$library->booksChunkedScore = pow($booksChunkedScore, 1.5) / pow(10 * $library->signUpDuration / $avgSignupDuration, 0.75); //NEW FAKE SCORE!!!
            //$library->booksChunkedScore = pow($booksChunkedScore, 1) / pow(1 + $library->signUpDuration / $avgSignupDuration, 1) / pow($booksChunkedScoreTail * 0.3, 1); //NEW FAKE SCORE!!!
            //$library->booksChunkedScore = pow($booksChunkedScore, 1) / pow($booksChunkedScoreTail * 0.3, 1); //NEW FAKE SCORE!!!
        } else {
            $library->booksChunked = collect();
            $library->booksChunkedScore = 0;
        }
    }
}


/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */

/*
// Signup Duration
$chart = new Chart('signupDurationY_' . $fileName);
$chart->plotLineY($libraries->sortBy('signUpDuration')->pluck('signUpDuration')->toArray());
*/

/*
// Award che danno i libri
$chart = new Chart('awardBooksY_' . $fileName);
$chart->plotLineY($books->sortBy('award')->pluck('award')->toArray());
*/

$avgSignupDuration = $libraries->avg('signUpDuration');
foreach ($libraries as $library)
    fullAlignLibrary($library->id);

//$libraries = $libraries->where('booksChunkedScore', '>', 50000);

$chart = new Chart('librariesBySignupDuration_' . $fileName);
$chart->plotMultiLineY([
    ['name' => 'SignupDuration', 'line' => $libraries->sortBy('signUpDuration')->pluck('signUpDuration')->toArray()],
    ['name' => 'booksChunkedScore', 'line' => $libraries->sortBy('signUpDuration')->pluck('booksChunkedScore')->toArray(), 'custom_axis' => true, 'side' => 'right']
]);
