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
            // Free vehicles
            foreach ($this->vehicles as $vehicle) {
                if ($vehicle->freeAt <= $this->currentStep) {
                    $this->freeVehicles[] = $vehicle;
                }
            }

            if (count($this->freeVehicles) === 0) {
                $this->currentStep++;
                continue;
            }

            // Calculate rides score
            if (count($this->rides) > 0) {
                //echo "\nRide scores:\n";
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

                // Assign rides to vehicles
                foreach ($ridesScore as $rideId => $rideScore) {
                    $currentRide = $this->rides[$rideId];
                    $selectedVehicleIdx = null;
                    foreach ($this->freeVehicles as $vehicleIdx => $vehicle) {
                        // Should calculate best vehicle for this ride
                        $selectedVehicleIdx = $vehicleIdx;
                        break;
                    }
                    if ($selectedVehicleIdx !== null) {
                        $selectedVehicle = $this->freeVehicles[$selectedVehicleIdx];
                        unset($this->freeVehicles[$selectedVehicleIdx]);
                        unset($this->rides[$rideId]);
                        $selectedVehicle->freeAt = max($this->currentStep + $currentRide->distance, $currentRide->earliestStartStep) + distanceBetween($selectedVehicle->currentRow, $selectedVehicle->currentColumn, $currentRide->startRow, $currentRide->startColumn);
                        $selectedVehicle->currentRow = $currentRide->startRow;
                        $selectedVehicle->currentColumn = $currentRide->startColumn;
                        if($selectedVehicle->freeAt < $this->stepsCount) {
                            $score = $currentRide->distance + ($selectedVehicle->freeAt <= $currentRide->latestFinishStep ? $this->perRideBonus : 0);
                        } else {
                            $score = 0;
                        }
                        //echo "[{$this->currentStep}] Assign ride #$rideId to vehicle #{$selectedVehicle->id} ($score points)\n";
                        $this->totalScore += $score;
                    }
                }
            }

            $this->currentStep++;
        }


        echo "\nTotal score: {$this->totalScore}\n";
    }
}

(new MMSolution)->run('a');
