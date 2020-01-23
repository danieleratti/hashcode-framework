<?php

$fileName = 'c'; //migliore dove c'è + margine (e senza bonus)

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

/* Soluzione per C -> NON contano i tempi!!!! cerchiamo di concatenare la più vicina */
foreach ($cars as $car) {
    echo "Car " . $car->id . "\n";
    while (true) {
        /** @var Car $car */
        $nearestDistance = 100000000000;
        $nearestRide = null;
        foreach ($rides as $ride) {
            /** @var Ride $ride */
            $distance = getDistance($car->r, $car->c, $ride->rStart, $ride->cStart);
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestRide = $ride;
            }
        }
        if ($nearestRide) { //TODO: threshold
            if ($car->getRidePoints($nearestRide) > 0)
                $SCORE += $car->takeRide($nearestRide);
            else
                break;
        }
    }
}

printOutput();

echo "Finito... SCORE = " . $SCORE;
/* C = 15751701 (94%) */
