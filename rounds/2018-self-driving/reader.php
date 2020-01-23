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
    public $cStart;
    public $rEnd;
    public $cEnd;

    public $earlyStart;
    public $latestFinish;

    public $minStart;
    public $maxStart;

    public $distance;

    private $takeAt;
    public $points;

    public function __construct($id, $rStart, $cStart, $rEnd, $cEnd, $earlyStart, $latestFinish)
    {
        $this->id = $id;
        $this->rStart = $rStart;
        $this->rEnd = $rEnd;
        $this->cStart = $cStart;
        $this->cEnd = $cEnd;
        $this->earlyStart = $earlyStart;
        $this->latestFinish = $latestFinish;
        $this->distance = getDistance($rStart, $cStart, $rEnd, $cEnd);

        $this->maxStart = $this->latestFinish - $this->distance;
        $this->minStart = $this->maxStart - $this->earlyStart;
    }

    public function isUseless()
    {
        return $this->distance - ($this->latestFinish - $this->earlyStart) > 0;
    }

    public function take($currentTime)
    {
        $this->takeAt = $currentTime;
        $this->points = $this->calculatePoints($currentTime + $this->distance);
    }

    private function calculatePoints($currentTime)
    {
        if ($currentTime > $this->latestFinish) {
            return 0;
        } else {
            return $this->distance + ($this->takeAt == $this->earlyStart ? Initializer::$BONUS : 0);
        }
    }
}

class Car
{
    public $id;
    public $r = 0;
    public $c = 0;
    public $freeAt = 0;
    public $rides = [];

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function canTakeRide(Ride $ride)
    {
        if ($ride->takeAt) {
            return false;
        }
    }

    public function takeRide(Ride $ride, $currentTime)
    {
        $t = $this->freeAt + getDistance($this->r, $this->c, $ride->rStart, $ride->cStart);
        $t = max($ride->earlyStart, $t);

        $freeAt = $t + $ride->distance;
        $r = $ride->rEnd;
        $c = $ride->cEnd;

        if ($freeAt > Initializer::$TIME) {
            echo "ATTENZIONE! Fuori tempo massimo\n";
            return 0;
        }

        Initializer::$RIDES->forget($ride->id);
        if ($this->freeAt > $ride->latestFinish) {
            echo "ATTENZIONE! 0 punti per questa ride\n";
            return 0;
        }

        $this->freeAt = $freeAt;
        $this->r = $r;
        $this->c = $c;

        $ride->take($currentTime);
        $this->rides[] = $ride;

        return $ride->points;
    }
}

class Initializer
{
    public static $ROWS;
    public static $COLUMNS;
    public static $F;
    public static $N;
    public static $BONUS;
    public static $TIME;
    public static $RIDES;
    public static $CARS;

    public function __construct(FileManager $fileManager)
    {
        $fileContent = $fileManager->get();

        $rows = explode("\n", $fileContent);
        list(self::$ROWS, self::$COLUMNS, self::$F, self::$N, self::$BONUS, self::$TIME) = explode(' ', $rows[0]);
        array_shift($rows);

        self::$RIDES = collect();
        foreach ($rows as $i => $row) {
            $row = explode(' ', $row);
            $ride = new Ride($i, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
            if (!$ride->isUseless()) {
                self::$RIDES->add($ride);
            }
        }
        self::$RIDES = self::$RIDES->keyBy('id');

        self::$CARS = collect();
        for ($i = 0; $i < self::$F; $i++) {
            self::$CARS->add(new Car($i));
        }
        self::$CARS = self::$CARS->keyBy('id');
    }
}
