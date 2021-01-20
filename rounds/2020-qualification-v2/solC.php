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
    global $remainingDays, $libraries;
    $library = $libraries[$lId];

    $doableBooksNumber = min(($remainingDays - $library->signUpDuration) * $library->shipsPerDay, $library->books->count());
    if ($doableBooksNumber > 0) {
        return $library->books->take($doableBooksNumber);
    } else {
        return collect();
    }
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

    if ($ranking->count()) {
        return $ranking->first();
    }
}

$result = [];
$points = 0;
$remainingDays = $countDays;
$maxPoints = $books->sum('award');

echo "MAX: $maxPoints\n";
while ($best = takeBestLibrary()) {
    $result[$best['id']] = $best['feasableBooks']->map(function ($book) {
        return $book->id;
    })->toArray();
    $points += $best['award'];

    $remainingDays -= $best['signUpDuration'];
    $removedEmptyLibs = 0;
    foreach ($best['feasableBooks'] as $book) {
        foreach ($book->inLibraries as $library) {
            $library->books->forget($book->id);

            if ($library->books->count() == 0) {
                $libraries->forget($library->id);
                $removedEmptyLibs++;
            }
        }
    }

    $remainingLibs = $libraries->count();
    echo "P: $points | GG: $remainingDays | RemovedLibs: $removedEmptyLibs | RemainingLibs: $remainingLibs\n";
}

echo "\n\nPUNTI: $points / $maxPoints\n\n";

