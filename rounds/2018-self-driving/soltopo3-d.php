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

class MultiRide
{
    public $rStart = 0;
    public $rEnd = 0;
    public $cStart = 0;
    public $cEnd = 0;
    public $tStart = 0; // initial: max(tStart[0], distance((0,0), position))
    public $tFinish = 0; // FINISH != end
    public $score = 0; // sum of all scores [NO bonus]
    public $rides = [];

    public function __construct()
    {
    }

    public function addRide(Ride $ride)
    {
        //checks
        if (count($this->rides) == 0) {
            $this->rStart = $ride->rStart;
            $this->cStart = $ride->cStart;
            $this->tStart = max(getDistance(0, 0, $this->rStart, $this->cStart), $ride->tStart);
            $this->tFinish = $this->tStart;
        }
        $this->tFinish = max($this->tFinish + $ride->distance, $ride->tStart);
        $this->rEnd = $ride->rEnd;
        $this->cEnd = $ride->cEnd;
        $this->score += $ride->distance;
    }
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

        $avgRidesDistances = $rides->avg('distance');

        foreach ($rides as $ride) {
            /** @var Ride $ride */
            if ($car->getRidePoints($ride) <= 0)
                continue;
            $performance = $car->getRidePerformance($ride); // [tra 0 e 1]

            // virtual performance
            $safeTime = $car->getSafeTime($ride); // should be small! [da 0 a migliaia]
            //$performance /= ($safeTime + 1) * 0.25;
            //if($safeTime > 0)
            //    $performance /= $safeTime/$avgRidesDistances;
            if ($safeTime > 1000)
                $performance /= 2;

            /*
            $distanceFromBaricentro = getDistance($ride->rEnd, $ride->cEnd, 2802, 1056);
            $x = min($distanceFromBaricentro, $T - $car->freeAt - $ride->distance - getDistance($car->r, $car->c, $ride->rStart, $ride->cStart));
            $performance /= $x;
            */

            if ($performance > $bestPerformance) {
                $bestPerformance = $performance;
                $bestRide = $ride;
            }
        }
        if ($bestRide) { //TODO: threshold
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
}

echo "Finito... SCORE = " . $SCORE;

printOutput();
