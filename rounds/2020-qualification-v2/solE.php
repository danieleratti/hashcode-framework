<?php

$fileName = 'e';

include 'reader-zem.php';

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

    $bestLibrary = null;
    foreach ($remainingLibs as $lId) {
        $library = $libraries[$lId];
        $feasable = takeFeasableBooks($lId);
        $award = $feasable->sum(function ($book) {
            return $book->award / (count($book->inLibraries) / 10 );
        });
//        $averageRarity = $feasable->avg(function ($book) {
//            return count($book->inLibraries);
//        });
        $score = ($award) / ($library->signUpDuration);

        if ($bestLibrary === null || $score > $bestLibrary['score']) {
            $bestLibrary = [
                'id' => $lId,
                'score' => $score,
                'award' => $feasable->sum('award'),
                'signUpDuration' => $library->signUpDuration,
                'feasableBooks' => $feasable
            ];
        }
    }

    if ($bestLibrary['score'] > 0) {
        return $bestLibrary;
    } else {
        return false;
    }
}

$result = [];
$points = 0;
$remainingDays = $countDays;
$maxPoints = $books->sum('award');

echo "MAX: $maxPoints\n";
while ($best = takeBestLibrary()) {
    $libraries->forget($best['id']);

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

