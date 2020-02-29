<?php


use Utils\ArrayUtils;
use Utils\Cerberus;
use Utils\Log;

require_once '../../bootstrap.php';
$fileName = null;
$kPow = 1.0;
$kPow2 = 1.0;
Cerberus::runClient(['fileName' => 'e', 'kPow' => 0.65, 'kPow2' => 1.0]);

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
/** @var Library[] $orderedSignuppedLibraries */
$orderedSignuppedLibraries = [];
/** @var Library[] $signuppedLibraries */
$signuppedLibraries = [];
/** @var Library[] $notSignuppedLibraries */
$notSignuppedLibraries = $libraries;
/** @var Library $currentSignupLibrary */
$currentSignupLibrary = null;

// Functions
function calcDeltaScoreFromSwitch($libIdFrom, $libIdTo, $bookId)
{
    global $orderedSignuppedLibraries;
    $libFrom = $orderedSignuppedLibraries[$libIdFrom]; // take the best from the unscanned
    $libTo = $orderedSignuppedLibraries[$libIdTo]; // take the worst from the scanned here

    $bestUnscannedBookLibFrom = array_slice($libFrom->books, 0, 1)[0];
    $worstScannedBookLibTo = array_slice($libTo->scannedBooks, -1, 1)[0];

    $score = $bestUnscannedBookLibFrom->award - $worstScannedBookLibTo->award;

    echo 'Score = ' . $score . "\n";

    return [
        'switchingBook' => $bookId,
        'pushBookFrom' => $bestUnscannedBookLibFrom->id,
        'popBookTo' => $worstScannedBookLibTo->id,
        'score' => $score,
    ];
}

function switchBook($switch)
{
    global $orderedSignuppedLibraries, $books;
    $libFrom = $orderedSignuppedLibraries[$switch['library_from']];
    $libTo = $orderedSignuppedLibraries[$switch['library_to']];

    $books[$switch['switch_book']]->unscan($libFrom);
    $books[$switch['pop_book_to']]->unscan($libTo);
    $books[$switch['switch_book']]->scan($libTo);
    $books[$switch['push_book_from']]->scan($libFrom);
}

// Algo


foreach ($books as $b) {
    $b->rAward = $b->award / pow(count($b->inLibraries), 1);
}
foreach ($libraries as $l) {
    foreach ($l->books as $b) {
        $l->rCurrentTotalAward += $b->rAward;
    }
}


for ($t = 0; $t < $countDays; $t++) {

    echo "[t = {$t}]\n";

    // Controllo se ha finito il signup la library corrente
    if ($currentSignupLibrary !== null && $t >= $currentSignupLibrary->signupFinishAt) {
        $currentSignupLibrary->finishSignup();
        $signuppedLibraries[$currentSignupLibrary->id] = $currentSignupLibrary;
        $orderedSignuppedLibraries[$currentSignupLibrary->id] = $currentSignupLibrary;
        $currentSignupLibrary = null;
        uasort($signuppedLibraries, function (Library $l1, Library $l2) {
            return count($l1->books) < count($l2->books);
        });
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
        $remainingTime = $countDays - $t;
        foreach ($notSignuppedLibraries as $nsl) {
            if ($fileName === 'f' || $fileName === 'e') {
                $nsl->recalculateDCurrentTotalAward($remainingTime);
            }
            $avanzo = $remainingTime - $nsl->signUpDuration;
            if ($avanzo > 0) {
                switch ($fileName) {
                    case 'a':
                    case 'b':
                    case 'c':
                        $score = $nsl->currentTotalAward / $nsl->signUpDuration; // 5689822
                        break;
                    case 'd':
                        $score = count($nsl->books) + $nsl->rCurrentTotalAward / 1000000; // per D
                        break;
                    case 'e':
                        //$score = pow($nsl->currentTotalAward * $nsl->shipsPerDay, 0.71) / $nsl->signUpDuration * pow($avanzo / $remainingTime, 8.9); // 5132856
                        //$score = pow($nsl->dLastChunkAward * $nsl->shipsPerDay, 0.71) / $nsl->signUpDuration * pow($avanzo / $remainingTime, 8.9); // 5175458
                        //$score = pow($nsl->currentTotalAward, 0.3) * $nsl->dLastChunkAward / $nsl->signUpDuration; // 
                        //$score = pow($nsl->dLastChunkAward * $nsl->shipsPerDay, $kPow) / $nsl->signUpDuration * pow($avanzo / $remainingTime, $kPow2); // CERBERUS

                        $score = pow($nsl->dLastChunkAward * $nsl->shipsPerDay, 0.65) / $nsl->signUpDuration * pow($avanzo / $remainingTime, 1.0); // 5182956 -> 5188091

                        break;
                    case 'f':
                        $score = $nsl->dCurrentTotalAward / pow($nsl->signUpDuration, 0.6); // 5345656
                        break;
                }
                //$score = $nsl->currentTotalAward / $nsl->signUpDuration; // per C
                //$score = count($nsl->books) + $nsl->rCurrentTotalAward / 1000000; // per D
                //$score = pow($nsl->dCurrentTotalAward * $nsl->shipsPerDay, 0.71) / $nsl->signUpDuration * pow($avanzo / $remainingTime, 8.9); // per E
                //$score = $nsl->dCurrentTotalAward / pow($nsl->signUpDuration, 0.6); // per F
                //$score = $nsl->rCurrentTotalAward / $nsl->signUpDuration * $avanzo / $remainingTime;
                //$score = $nsl->currentTotalAward * $nsl->shipsPerDay / $nsl->signUpDuration * $avanzo;
                //$score = $nsl->currentTotalAward * $nsl->shipsPerDay;
                //$score = pow($nsl->currentTotalAward * $nsl->shipsPerDay, 0.71) / $nsl->signUpDuration * pow($avanzo / $remainingTime, 9);

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

echo "\n\nTotal initial score: {$totalScore}\n\n";

// Output

$output = '';
foreach ($orderedSignuppedLibraries as $lId => $sl) {
    if (count($sl->scannedBooks) === 0) {
        unset($orderedSignuppedLibraries[$lId]);
    }
}
$output .= count($signuppedLibraries) . "\n";

$lastDayAwards = [];
foreach ($orderedSignuppedLibraries as $sl) {
    $scannedBooksCount = count($sl->scannedBooks);
    //$output .= "{$sl->id} {$scannedBooksCount}\n";
    $scannedIds = [];

    $lastDayAward = 0;
    $scannedBookNumber = 0;

    Log::out("Analyzing " . $sl->id . " for switching...");

    $signuppedLibrariesIds = array_keys($orderedSignuppedLibraries);

    foreach ($sl->scannedBooks as $sb) {
        $scannedIds[] = $sb->id;
        //if ($scannedBookNumber >= count($sl->scannedBooks) - $sl->shipsPerDay)
        //    $lastDayAward += $sb->award;
        $scannedBookNumber++;

        $deltaScores = [];
        $sbInLibraries = array_keys($sb->inLibraries);
        $intersectedLibrariesForSB = array_intersect($signuppedLibrariesIds, $sbInLibraries);
        foreach ($intersectedLibrariesForSB as $ilId) {
            if ($ilId != $sl->id) { // not the selected one
                $check = calcDeltaScoreFromSwitch($sl->id, $ilId, $sb->id);
                if ($check['score'] > 0) {
                    $deltaScores[] = [
                        'switch_book' => $sb->id,
                        'library_from' => $sl->id,
                        'library_to' => $ilId,
                        'push_book_from' => $check['pushBookFrom'],
                        'pop_book_to' => $check['popBookTo'],
                        'delta_score' => $check['score'],
                    ];
                }
            }
        }
        if (count($deltaScores) > 0) {
            ArrayUtils::array_keysort($deltaScores, 'delta_score', SORT_DESC);
            foreach ($deltaScores as $bestSwitch) {
                switchBook($bestSwitch);
                $totalScore += $bestSwitch['delta_score'];
                Log::out('Book ' . $sb->id . ' from lib ' . $sl->id . ' to ' . $bestSwitch['library_to'] . '... Gained +' . $bestSwitch['delta_score'] . ' points! FINAL SCORE = ' . $totalScore);
                break;
            }
        }
    }
    //$output .= implode(" ", $scannedIds) . "\n";
    //Log::out("Lib " . $sl->id . " => signupDuration=".$sl->signUpDuration." // lastDayAward=" . $lastDayAward . ' // ships = ' . $sl->shipsPerDay . ' // awardDivShips = ' . round($lastDayAward / $sl->shipsPerDay, 2) . '');
}

foreach ($orderedSignuppedLibraries as $sl) {
    $scannedBooksCount = count($sl->scannedBooks);
    $output .= "{$sl->id} {$scannedBooksCount}\n";
    $scannedIds = [];

    foreach ($sl->scannedBooks as $sb) {
        $scannedIds[] = $sb->id;
    }
    $output .= implode(" ", $scannedIds) . "\n";
}

$fileManager->output($output);

echo "\n\nTotal final score: {$totalScore}\n\n";

/*
 * Concept: runno questo primo algo (sol-topomm-2-analysis @ e)
 * Creo algo sol-topomm-2-analysis-part-2:
 * Legge input di sol-topomm-2-analysis
 * Parte dalla cima e valuta per ogni libro [A]:
 *  1) Quanti punti farebbe (perderebbe) se fosse tolto dalla lib (e quindi fosse messo il primo libro [B] degli scartati)
 *  2.1) Tra tutte le (eventuali) lib alternative (tra le successive?) che lo hanno, valuta per ognuna il punteggio perso
 *       per l'aver tolto il peggiore tra i libri selezionati [C] (per far posto ad [A])
 *  2.2) Il delta score dell'operazione è quindi ovviamente [B]-[C] (in quanto [A] rimane, viene solo switchato tra un lib e un'altra)
 * 3) Calcola tra tutte le possibilità quella con delta score maggiore, esegue lo switch e aggiunge il delta score al punteggio finale
 * 4) Itero N volte?
 *
 * 2020-02-29 15:02:21 =>    Book 79903 (award=250) present also in 244
2020-02-29 15:02:21 =>    Book 79903 (award=250) present also in 273
2020-02-29 15:02:21 =>    Book 23967 (award=250) present also in 970
2020-02-29 15:02:21 =>    Book 63011 (award=249) present also in 232
2020-02-29 15:02:21 =>    Book 56764 (award=248) present also in 512
2020-02-29 15:02:21 =>    Book 56764 (award=248) present also in 457
2020-02-29 15:02:21 =>    Book 87189 (award=248) present also in 691
2020-02-29 15:02:21 =>    Book 30097 (award=248) present also in 443
2020-02-29 15:02:21 =>    Book 30097 (award=248) present also in 828
2020-02-29 15:02:21 =>    Book 90748 (award=247) present also in 834
2020-02-29 15:02:21 =>    Book 90748 (award=247) present also in 56
2020-02-29 15:02:21 =>    Book 75939 (award=247) present also in 244
...
2020-02-29 15:06:44 => Lib 602 => signupDuration=1 // lastDayAward=260 // ships = 2 // awardDivShips = 130
...
...
2020-02-29 15:06:44 => Lib 972 => signupDuration=1 // lastDayAward=254 // ships = 2 // awardDivShips = 127
2020-02-29 15:06:44 => Lib 522 => signupDuration=1 // lastDayAward=251 // ships = 2 // awardDivShips = 125.5
2020-02-29 15:06:44 => Lib 717 => signupDuration=1 // lastDayAward=233 // ships = 2 // awardDivShips = 116.5
2020-02-29 15:06:44 => Lib 157 => signupDuration=1 // lastDayAward=244 // ships = 2 // awardDivShips = 122
2020-02-29 15:06:44 => Lib 72 => signupDuration=1 // lastDayAward=248 // ships = 2 // awardDivShips = 124
2020-02-29 15:06:44 => Lib 100 => signupDuration=1 // lastDayAward=234 // ships = 2 // awardDivShips = 117
2020-02-29 15:06:44 => Lib 715 => signupDuration=1 // lastDayAward=233 // ships = 2 // awardDivShips = 116.5
 *
 * */


