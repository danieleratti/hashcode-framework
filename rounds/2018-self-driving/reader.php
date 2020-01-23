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
    }
}

class Car
{
    public $id;
    public $r = 0;
    public $c = 0;
    public $freeAt = 0;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function takeRide(Ride $ride)
    {
        global $T, $B, $rides;

        $t = $this->freeAt + getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = max($ride->tStart, $t);

        $this->freeAt = $t + $ride->distance;
        $this->r = $ride->rEnd;
        $this->c = $ride->cEnd;

        if ($this->freeAt > $T)
            die('tempo fine ride > T');

        $rides->forget($ride->id);
        if ($this->freeAt > $ride->tEnd) {
            echo "ATTENZIONE! 0 punti per questa ride";
            return 0;
        }

        return $ride->distance + ($t == $ride->tStart ? $B : 0);
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
