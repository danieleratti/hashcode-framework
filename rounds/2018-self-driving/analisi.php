<?php

/* Distanza tra punto medio partenze e fine ride è un malus
Versione base (con 300) : SCORE = 572744 (est. 11454880) 20esima macchina

*/

$fileName = 'd';

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

echo $rides->where('distance', '>', 10000)->sum('distance');
