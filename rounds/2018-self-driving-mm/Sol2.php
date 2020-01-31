<?php

ini_set('memory_limit', '16G');
include_once(__DIR__ . '/models.php');
include_once(__DIR__ . '/functions.php');

class MMSolution
{
    public int $rowsCount = 0;
    public int $columnCount = 0;
    public int $vehiclesCount = 0;
    public int $ridesCount = 0;
    public int $perRideBonus = 0;
    public int $stepsCount = 0;
    /** @var Ride[] $rides */
    public array $rides = [];
    /** @var Vehicle[] $vehicles */
    public array $vehicles = [];

    public int $currentStep = 0;
    /** @var Vehicle[] $freeVehicles */
    public array $freeVehicles = [];
    public int $totalScore = 0;

    public const MAX_ANALYZED_RIDES = 30;

    public function readInput(string $inputLetter): void
    {
        $name = [
            'a' => 'a_example',
            'b' => 'b_should_be_easy',
            'c' => 'c_no_hurry',
            'd' => 'd_metropolis',
            'e' => 'e_high_bonus',
        ][$inputLetter];
        $content = explode("\n", file_get_contents(__DIR__ . '/input/' . $name . '.in'));
        $firstLine = explode(" ", $content[0]);
        $this->rowsCount = (int)$firstLine[0];
        $this->columnCount = (int)$firstLine[1];
        $this->vehiclesCount = (int)$firstLine[2];
        $this->ridesCount = (int)$firstLine[3];
        $this->perRideBonus = (int)$firstLine[4];
        $this->stepsCount = (int)$firstLine[5];
        for ($i = 1; $i <= $this->ridesCount; $i++) {
            $rideLine = explode(" ", $content[$i]);
            $this->rides[] = new Ride(
                (int)($i - 1),
                (int)$rideLine[0],
                (int)$rideLine[1],
                (int)$rideLine[2],
                (int)$rideLine[3],
                (int)$rideLine[4],
                (int)$rideLine[5]
            );
        }
        for ($i = 0; $i < $this->vehiclesCount; $i++) {
            $this->vehicles[] = new Vehicle($i);
        }
    }

    public function run(string $inputLetter): void
    {
        // Read input
        $this->readInput($inputLetter);

        // Solution

        while ($this->currentStep < $this->stepsCount) {

            if (count($this->rides) === 0) break;

            // Get min freeAt and vehicles
            $minFreeAt = $this->stepsCount;
            $nextVehicles = [];
            foreach ($this->vehicles as $vehicle) {
                if ($vehicle->freeAt < $minFreeAt) {
                    $minFreeAt = $vehicle->freeAt;
                    $nextVehicles = [$vehicle];
                }
                if ($vehicle->freeAt === $minFreeAt) {
                    $nextVehicles[] = $vehicle;
                }
            }
            $this->currentStep = $minFreeAt;

            // Calculate rides score
            //echo "\n[{$this->currentStep}]Rides score:\n";
            $ridesScore = [];
            foreach ($this->rides as $ride) {
                if ($ride->distance + $this->currentStep <= $ride->latestFinishStep) {
                    $urgency = ($ride->distance + $this->currentStep) / $ride->latestFinishStep;
                } else {
                    $urgency = 0;
                }
                $score = $ride->distance * (1 + $urgency);
                $ridesScore[$ride->id] = $score;
                //echo "{$ride->id} => $score\n";
            }
            arsort($ridesScore);

            // Search the best ride for each vehicle
            foreach ($nextVehicles as $vehicle) {

                // Search the best ride
                $selectedRide = null;
                $maxRideScore = -1;
                foreach ($ridesScore as $rideId => $rideScore) {
                    $currentRide = $this->rides[$rideId];
                    $selectedRide = $currentRide;
                    break;
                }

                // Assign the ride to the vehicle
                unset($this->rides[$selectedRide->id]);
                unset($ridesScore[$selectedRide->id]);
                $vehicle->freeAt = max($this->currentStep + $selectedRide->distance, $selectedRide->earliestStartStep) + distanceBetween($vehicle->currentRow, $vehicle->currentColumn, $selectedRide->startRow, $selectedRide->startColumn);
                $vehicle->currentRow = $selectedRide->startRow;
                $vehicle->currentColumn = $selectedRide->startColumn;
                if ($vehicle->freeAt < $this->stepsCount) {
                    $score = $selectedRide->distance + ($vehicle->freeAt <= $selectedRide->latestFinishStep ? $this->perRideBonus : 0);
                } else {
                    $score = 0;
                }
                //echo "[{$this->currentStep}] Assign ride #{$selectedRide->id} to vehicle #{$vehicle->id} ($score points)\n";
                $this->totalScore += $score;

                // Check if there are other rides available
                if (count($this->rides) === 0) break;
            }

        }


        echo "\nTotal score: {$this->totalScore}\n";
    }
}

(new MMSolution)->run('e');
