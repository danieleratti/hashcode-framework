<?php

use Utils\Chart;
use Utils\Collection;

$fileName = 'e';

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


        $percentileChunkScore = 0;
        if($booksChunked->count() + $library->signUpDuration >= ($countDays - $currentDay)) {
            $percentileChunkScore = $booksChunked[$booksChunked->count()-1]->sum('award');
        }
        $library->percentileChunkScore = $percentileChunkScore;
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

foreach($libraries as $library) {
    /** @var Library $library*/
    foreach($library->books as $book) {
        foreach($book->inLibraries as $idInLibrary => $inlibrary)
            $library->librariesConnected[$idInLibrary] = $inlibrary;
    }
    $library->librariesConnectedCount = count($library->librariesConnected);
}
/*
$chart = new Chart('librariesConnectedCount_' . $fileName);
$chart->plotMultiLineY([
    ['name' => 'librariesConnectedCount', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('librariesConnectedCount')->toArray()],
    ['name' => 'booksChunkedScore', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('booksChunkedScore')->toArray(), 'custom_axis' => true, 'side' => 'right'],
    //['name' => 'booksNumber', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('booksNumber')->toArray(), 'custom_axis' => true, 'side' => 'right']
]);
*/

$chart = new Chart('percentileChunkedScore_' . $fileName);
$chart->plotMultiLineY([
    ['name' => 'percentileChunkScore', 'line' => $libraries->sortBy('percentileChunkScore')->pluck('percentileChunkScore')->toArray()],
    ['name' => 'booksChunkedScore', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('booksChunkedScore')->toArray(), 'custom_axis' => true, 'side' => 'right'],
    //['name' => 'booksNumber', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('booksNumber')->toArray(), 'custom_axis' => true, 'side' => 'right'],
]);
