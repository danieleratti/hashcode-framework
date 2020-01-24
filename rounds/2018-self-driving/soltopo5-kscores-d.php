<?php

/* Distanza tra punto medio partenze e fine ride è un malus
Versione base (con 300) : SCORE = 572744 (est. 11454880) 20esima macchina

*/

$fileName = 'd'; //b,c,d

include 'reader.php';

function printOutput()
{
    /** @var Car $car */
    global $cars, $fileManager;
    $output = [];
    foreach ($cars as $car) {
        $output[] = $car->toString();
    }
    $fileManager->output(implode("\n", $output));
}

$SCORE = 0;

// ROUND centro (R=)
$RRound = 2779;
$CRound = 1055;
$RadiusRound = 900;

/*
$rides = $rides->filter(function (Ride $ride) use ($RRound, $CRound, $RadiusRound) {
    $hypoStart = sqrt(pow($ride->rStart - $RRound, 2) + pow($ride->cStart - $CRound, 2));
    $hypoEnd = sqrt(pow($ride->rEnd - $RRound, 2) + pow($ride->cEnd - $CRound, 2));
    return $hypoStart <= $RadiusRound && $hypoEnd <= $RadiusRound;
});
*/

/* Soluzione per C -> NON contano i tempi!!!! cerchiamo di concatenare la più vicina */
foreach ($cars as $ncar => $car) {
    while (true) {
        /** @var Car $car */
        $bestPerformance = 0;
        $bestRide = null;

        foreach ($rides as $ride) {
            /** @var Ride $ride */
            $points = $car->getRidePoints($ride);
            if ($points <= 0)
                continue;

            $k1 = 1; // :(((
            $k2 = 1; // :((
            $k3 = 0; // :(

            $distXride = getDistance($car->r, $car->c, $ride->rStart, $ride->cStart);
            $waitingTime = max(0, $ride->tStart - ($car->freeAt + $distXride));
            $hurryTime = $ride->tLastStart - ($car->freeAt + $distXride + $waitingTime);

            //$performance = $points / (1 + $k1 * $waitingTime + $k2 * $distXride + $k3 * $hurryTime);
            $performance = 1 / (1 + $k1 * $waitingTime + $k2 * $distXride + $k3 * $hurryTime);

            //if ($hurryTime > 300)
            //    $performance /= 1.1;

            if ($T - $car->freeAt < 25000 && $ride->distance > 2500)
                $performance /= 1.1;

            if ($performance > $bestPerformance) {
                $bestPerformance = $performance;
                $bestRide = $ride;
            }
        }
        if ($bestRide) { //TODO: threshold accettazione
            //echo "Best performance = " . $bestPerformance . "\n";
            if ($car->getRidePoints($bestRide) > 0)
                $SCORE += $car->takeRide($bestRide);
            else
                break;
        } else break;
    }
    $foo = 1;
    echo "Done Car " . $car->id . " (" . $car->score . ")\n";
    echo "SCORE = " . $SCORE . " (est. " . round($SCORE / $ncar * $F) . ")\n";
    //die();
}

echo "Finito... SCORE = " . $SCORE;

printOutput();
