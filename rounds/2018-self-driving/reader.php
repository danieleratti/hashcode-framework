<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

function getDistance($rStart, $cStart, $rEnd, $cEnd)
{
    return abs($rEnd - $rStart) + abs($cEnd - $cStart);
}

class Ride
{
    public $id;
    public $rStart;
    public $rEnd;
    public $cStart;
    public $cEnd;
    public $tStart;
    public $tEnd;
    public $distance;
    public $tLastStart;

    public function __construct($id, $rStart, $cStart, $rEnd, $cEnd, $tStart, $tEnd)
    {
        $this->id = $id;
        $this->rStart = $rStart;
        $this->rEnd = $rEnd;
        $this->cStart = $cStart;
        $this->cEnd = $cEnd;
        $this->tStart = $tStart;
        $this->tEnd = $tEnd;
        $this->distance = getDistance($rStart, $cStart, $rEnd, $cEnd);
        $this->tLastStart = $tEnd - $this->distance;
    }
}

class Car
{
    public $id;
    public $r = 0;
    public $c = 0;
    public $freeAt = 0;
    public $rides;

    public $usefulTime = 0;
    public $unusefulTime = 0;
    public $timePerformance = 0;

    public $score = 0;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getRidePoints(Ride $ride)
    {
        global $B, $T;

        $t = $this->freeAt + getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = max($ride->tStart, $t);

        if ($this->freeAt > $T)
            return 0;

        if ($t + $ride->distance > $ride->tEnd)
            $points = 0;
        else
            $points = $ride->distance + ($t == $ride->tStart ? $B : 0);

        return $points;
    }

    public function getRidePerformance(Ride $ride)
    {
        $useful = $ride->distance;
        $unuseful = getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = $this->freeAt + $unuseful;
        if($t < $ride->tStart)
            $unuseful += $ride->tStart - $t;
        return $useful / ($useful + $unuseful);
    }

    public function getSafeTime(Ride $ride)
    {
        $unuseful = getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = $this->freeAt + $unuseful;
        return $ride->tEnd - $t - $ride->distance;
    }

    public function takeRide(Ride $ride)
    {
        global $T, $B, $rides;

        echo "Car {$this->id} is taking ride {$ride->id} (TBefore={$this->freeAt})\n";

        $t = $this->freeAt + getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = max($ride->tStart, $t);

        $this->unusefulTime += $t - $this->freeAt;
        $this->usefulTime += $ride->distance;
        $this->timePerformance = $this->getTimePerformance();

        $this->freeAt = $t + $ride->distance;
        $this->r = $ride->rEnd;
        $this->c = $ride->cEnd;

        if ($this->freeAt > $T)
            die("FATAL: tempo fine ride > T\n");

        $rides->forget($ride->id);
        $this->rides[] = $ride->id;
        if ($this->freeAt > $ride->tEnd) {
            echo "ATTENZIONE! 0 punti per questa ride\n";
            return 0;
        }

        $score = $ride->distance + ($t == $ride->tStart ? $B : 0);
        $this->score += $score;

        echo "Tafter={$this->freeAt} // Score={$this->score}\n";
        return $score;
    }

    public function getTimePerformance()
    {
        return round($this->usefulTime / ($this->usefulTime + $this->unusefulTime), 2);
    }

    public function toString()
    {
        return count($this->rides) . ' ' . implode(' ', $this->rides);
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$fileContent = $fileManager->get();

$rows = explode("\n", $fileContent);
list($R, $C, $F, $N, $B, $T) = explode(' ', $rows[0]);
array_shift($rows);

$rides = collect();
foreach ($rows as $i => $row) {
    $row = explode(' ', $row);
    $rides->add(new Ride($i, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]));
}
$rides = $rides->keyBy('id');

$cars = collect();
for ($i = 0; $i < $F; $i++) {
    $cars->add(new Car($i));
}
$cars = $cars->keyBy('id');
