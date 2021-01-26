<?php

$fileName = 'b';

include 'gz-reader.php';

use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

class Manager
{
    public $freeArms;
    public $placedArms = [];
    public $tasks;
    public $mountPoints;
    public $remainingSteps = 0;
    public $mapStatus;
    public $w, $h;

    public function __construct($initialArms, $tasks, $mountPoints, $steps, $h, $w)
    {
        $this->freeArms = $initialArms;
        $this->tasks = $tasks;
        $this->mountPoints = $mountPoints;
        $this->remainingSteps = $steps;
        $this->h = $h;
        $this->w = $w;
        $this->mapStatus = [];

        for ($y = 0; $y < $h; $y++) {
            $newRow = [];
            for ($x = 0; $x < $w; $x++) {
                $newRow[] = 0;
            }

            $this->mapStatus[] = $newRow;
        }

        foreach ($this->mountPoints as $mountPoint) {
            $this->mapStatus[$mountPoint->y][$mountPoint->x] = 1;
        }
    }

    public function printMap()
    {
        $visualStandard = new VisualStandard($this->h, $this->w);

        for ($y = 0; $y < $this->h; $y++) {
            for ($x = 0; $x < $this->w; $x++) {
                if ($this->mapStatus[$y][$x] == 1) {
                    $visualStandard->setPixel($y, $x, Colors::blue9);
                }
            }
        }

        foreach ($this->placedArms as $arm) {
            $visualStandard->setPixel($arm->y, $arm->x, Colors::green5);
        }

        $visualStandard->save('freeMap');
    }

    public function hasFreeArms(): bool
    {
        return count($this->freeArms) > 0;
    }

    public function placeArm($mountPoint, $task)
    {
        if ($this->hasFreeArms()) {
            $toPlace = $this->freeArms->pop();

            $toPlace->mountPoint = $mountPoint;
            $toPlace->x = $mountPoint->x;
            $toPlace->y = $mountPoint->y;
            $toPlace->currentTask = $task;

            $this->tasks->forget($task->id);
            $this->mountPoints->forget($mountPoint->id);

            $this->placedArms[] = $toPlace;
        }
    }

    public function findClosestMountPoint($y, $x): array
    {
        $closestMountPoint = null;
        $closestDistance = null;
        foreach ($this->mountPoints as $mountPoint) {
            $distance = abs($x - $mountPoint->x) + abs($y - $mountPoint->y);

            if ($closestMountPoint == null || $distance < $closestDistance) {
                $closestMountPoint = $mountPoint;
                $closestDistance = $distance;
            }
        }

        return [
            'mountPoint' => $closestMountPoint,
            'distance' => $closestDistance
        ];
    }

    public function findBestTask(): array
    {
        $bestTask = null;
        $bestScore = null;
        $closestMountPoint = null;
        foreach ($this->tasks as $task) {
            $result = $this->findClosestMountPoint($task->startY, $task->startX);
            $mountPoint = $result['mountPoint'];
            $distance = $result['distance'];

            $score = $task->score / ($task->nSteps + $distance);

            if ($bestTask == null || $score < $bestScore) {
                $bestTask = $task;
                $bestScore = $score;
                $closestMountPoint = $mountPoint;
            }
        }

        return [
            'task' => $bestTask,
            'mountPoint' => $closestMountPoint
        ];
    }
}

function fromPathToCoords($startY, $startX, $path, $maxY, $maxX): array
{
    $y = $startY;
    $x = $startX;

    $coords = [[$y, $x]];

    foreach (str_split($path) as $command) {
        switch ($command) {
            case 'R':
            {
                $x = min($x + 1, $maxX);
                break;
            }
            case 'L':
            {
                $x = max($x - 1, 0);
                break;
            }
            case 'U':
            {
                $y = max($y - 1, 0);
                break;
            }
            case 'D':
            {
                $y = min($y + 1, $maxY);
                break;
            }
            case 'W':
            default:
            {
                break;
            }
        }

        $coords[] = [$y, $x];
    }

    return $coords;
}

$manager = new Manager($ARMS, $TASKS, $MOUNT_POINTS, $N_STEPS, $H, $W);

Log::out('init placing arms');
while ($manager->hasFreeArms()) {
    $task = $manager->findBestTask();
    $manager->placeArm($task['mountPoint'], $task['task']);
    Log::out('arm placed, remaining: ' . count($manager->freeArms), $level = 1);
}
Log::out('placed all arms');

Log::out('starting to iterate over steps');
$manager->printMap();

print_r(fromPathToCoords(0, 0, 'RRRRDDUUUUWWUUDD', $H, $W));

