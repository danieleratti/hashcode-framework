<?php

$fileName = 'f';

include 'reader-mm.php';

/**
 * @var int $countBooks
 * @var int $countLibraries
 * @var int $countDays
 * @var Book[] $books
 * @var Library[] $libraries
 */

/** @var int $totalScore */
$totalScore = 0;
/** @var Book[] $scannedBooks */
$scannedBooks = [];
/** @var Book[] $notScannedBooks */
$notScannedBooks = $books;
/** @var Library[] $signuppedLibraries */
$signuppedLibraries = [];
/** @var Library[] $notSignuppedLibraries */
$notSignuppedLibraries = $libraries;
/** @var Library $currentSignupLibrary */
$currentSignupLibrary = null;

// Algo

for ($t = 0; $t < $countDays; $t++) {

    echo "[t = {$t}]\n";

    // Controllo se ha finito il signup la library corrente
    if ($currentSignupLibrary !== null && $t >= $currentSignupLibrary->signupFinishAt) {
        $currentSignupLibrary->finishSignup();
        $signuppedLibraries[$currentSignupLibrary->id] = $currentSignupLibrary;
        $currentSignupLibrary = null;
    }

    // Per le library già signuppate scanno i libri in ordine di award e al max shipsPerDay
    foreach ($signuppedLibraries as $sl) {
        $count = 0;
        foreach ($sl->books as $b) {
            /*
            if (isset($scannedBooks[$b->id])) {
                die("Errore");
            }
            */
            $scannedBooks[$b->id] = $b;
            $totalScore += $b->award;
            // Tolgo i libri dalle altre library che lo hanno
            $b->scan($sl);
            $count++;
            if ($count >= $sl->shipsPerDay) break;
        }
    }

    if ($currentSignupLibrary === null) {
        // Do degli score alle library non ancora signuppate
        $libraryScores = [];
        foreach ($notSignuppedLibraries as $nsl) {
            if ($countDays - $t - $nsl->signUpDuration > 0) {
                $score = $nsl->currentTotalAward * $nsl->shipsPerDay / $nsl->signUpDuration;  /* - ($countDays - $nsl->signUpDuration)*/
                $libraryScores[$nsl->id] = $score;
            }
        }
        arsort($libraryScores);

        // Prendo la migliore e Faccio partire il processo di signup per la library con lo score max
        reset($libraryScores);
        $bestLibraryId = key($libraryScores);
        if ($bestLibraryId !== null /*&& $t + $notSignuppedLibraries[$bestLibraryId]->signUpDuration < $countDays*/) {
            $currentSignupLibrary = $notSignuppedLibraries[$bestLibraryId];
            $currentSignupLibrary->startSignup($t);
            unset($notSignuppedLibraries[$currentSignupLibrary->id]);
        }
    }

}

echo "\n\nTotal score: {$totalScore}";

// Output

$output = '';
foreach ($signuppedLibraries as $lId => $sl) {
    if (count($sl->scannedBooks) === 0) {
        unset($signuppedLibraries[$lId]);
    }
}
$output .= count($signuppedLibraries) . "\n";
foreach ($signuppedLibraries as $sl) {
    $scannedBooksCount = count($sl->scannedBooks);
    $output .= "{$sl->id} {$scannedBooksCount}\n";
    $scannedIds = [];
    foreach ($sl->scannedBooks as $sb) {
        $scannedIds[] = $sb->id;
    }
    $output .= implode(" ", $scannedIds) . "\n";
}

$fileManager->output($output);
