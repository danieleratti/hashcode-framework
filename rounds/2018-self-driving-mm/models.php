<?php

include_once(__DIR__ . '/functions.php');

class Ride
{
    public int $id = 0;
    public int $startRow = 0;
    public int $startColumn = 0;
    public int $finishRow = 0;
    public int $finishColumn = 0;
    public int $earliestStartStep = 0;
    public int $latestFinishStep = 0;
    // Calulated
    public int $distance = 0;

    //public int $latestStartStep = 0;

    public function __construct(int $id, int $startRow, int $startColumn, int $finishRow, int $finishColumn, int $earliestStartStep, int $latestFinishStep)
    {
        $this->id = $id;
        $this->startRow = $startRow;
        $this->startColumn = $startColumn;
        $this->finishRow = $finishRow;
        $this->finishColumn = $finishColumn;
        $this->earliestStartStep = $earliestStartStep;
        $this->latestFinishStep = $latestFinishStep;
        $this->distance = distanceBetween($this->startRow, $this->startColumn, $this->finishRow, $this->finishColumn);
        //$this->latestOnTimeStartStep =
    }
}

class Vehicle
{
    public int $id;
    // Mutable
    public int $currentRow = 0;
    public int $currentColumn = 0;
    public int $freeAt = 0;
    /** @var Ride[] $rides */
    public array $rides = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

class RideSimulation
{
    public static int $perRideBonus = 0;

    public Ride $ride;
    public Vehicle $vehicle;
    public int $currentStep;
    public int $vehicleDistance;
    public int $timeTaken;
    public int $waitTime;
    public int $timeUntilDeparture;
    public int $willFreeAt;
    public int $revenue;

    public function __construct(Ride $ride, Vehicle $vehicle, int $currentStep)
    {
        $this->ride = $ride;
        $this->vehicle = $vehicle;
        $this->currentStep = $currentStep;
        $this->vehicleDistance = distanceBetween($vehicle->currentRow, $vehicle->currentColumn, $ride->startRow, $ride->startColumn);
        $this->timeUntilDeparture = max($this->vehicleDistance, $ride->earliestStartStep - $currentStep);
        $this->timeTaken = $ride->distance + $this->timeUntilDeparture;
        $this->willFreeAt = $currentStep + $this->timeTaken;
        $this->waitTime = $ride->earliestStartStep - $currentStep - $this->vehicleDistance;
        $this->revenue = $this->ride->distance + ($this->waitTime >= 0 ? self::$perRideBonus : 0);
    }

    public function isPossible(): bool
    {
        return $this->willFreeAt <= $this->ride->latestFinishStep;
    }
}
