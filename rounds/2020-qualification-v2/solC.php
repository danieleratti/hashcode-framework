<?php

use Utils\Collection;
use Utils\Log;
use Utils\Analysis\Analyzer;

$fileName = 'c';

include 'reader.php';

/** @var Books[] $books */
/** @var Libraries[] $libraries */
/** @var int $countDays */

function takeFeasableBooks($lId)
{
    global $remainingDays, $libraries, $usedBooks;
    $library = $libraries[$lId];
    $remainingBooks = $library->books->filter(function ($book)  use ($usedBooks) {
        return !in_array($book->id, $usedBooks);
    })->sortByDesc('award');

    $doableBooksNumber = min(($remainingDays - $library->signUpDuration) * $library->shipsPerDay, $remainingBooks->count());
    return $remainingBooks->take($doableBooksNumber);
}

function takeBestLibrary()
{
    global $libraries, $result;
    $remainingLibs = $libraries->keys()->diff(array_keys($result));

    $ranking = $remainingLibs->map(function ($lId) use ($libraries) {
        $library = $libraries[$lId];
        $feasable = takeFeasableBooks($lId);
        $award = $feasable->sum('award');
        $score = $award / $library->signUpDuration;

        return [
            'id' => $lId,
            'score' => $score,
            'award' => $award,
            'signUpDuration' => $library->signUpDuration,
            'feasableBooks' => $feasable
        ];
    })
        ->sortByDesc('score')
        ->filter(function ($rank) {
            return $rank['score'] > 0;
        });

    if($ranking->count()) {
        return $ranking->first();
    }
}

$result = [];
$points = 0;
$usedBooks = [];
$remainingDays = $countDays;
$maxPoints = $books->sum('award');

echo "MAX: $maxPoints\n";
while ($best = takeBestLibrary()) {
    $result[$best['id']] = $best['feasableBooks']->map(function ($book) {
        return $book->id;
    })->toArray();
    $points += $best['award'];

    $remainingDays -= $best['signUpDuration'];
    foreach ($best['feasableBooks'] as $book) {
        $usedBooks[] = $book->id;
    }

    echo "P: $points | GG: $remainingDays\n";
}

print_r($result);
echo "\n\nPUNTI: $points / $maxPoints\n\n";

