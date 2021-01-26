<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;

$fileName = $fileName ?: 'a';

$W = 0;
$H = 0;
$N_MOUNT_POINTS = 0;
$N_ASSEMBLY_POINTS = 0;
$N_TASKS = 0;
$N_ARMS = 0;
$N_STEPS = 0;

$CELLS = [];
$MAP = []; // [x][y]=m (mounting point),a (assembly point)
$ASSEMBLY_POINTS = [];
$MOUNT_POINTS = [];
$TASKS = [];

class Cell
{
    public $x, $y;
    public MountPoint $mountPoint;
    public AssemblyPoint $assemblyPoint;

    public $mountedArm;

    public $tempArm;
    public $freeAt;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}

class AssemblyPoint
{
    public $id;
    public $x, $y;
    /** @var int $starts */
    public $starts = 0;
    /** @var int $finishes */
    public $finishes = 0;
    /** @var int $middles */
    public $middles = 0;
    /** @var int $singles */
    public $singles = 0;
    public $startingTasks = [];
    public $endingTasks = [];
    public $singleTasks = [];

    static $_nextId = 0;

    public function __construct($x, $y)
    {
        $this->id = static::$_nextId++;
        $this->x = $x;
        $this->y = $y;
    }

    public static function get($x, $y, $status, $task)
    {
        global $MAP, $ASSEMBLY_POINTS;
        if ($ap = $MAP[$x][$y]) {
            /** @var AssemblyPoint $ap */
        } else {
            $ap = new AssemblyPoint($x, $y);
            $ASSEMBLY_POINTS[$ap->id] = $ap;
            $MAP[$x][$y] = $ap;
        }
        switch ($status) {
            case 'start':
                $ap->starts++;
                $ap->startingTasks[] = $task;
                break;
            case 'middle';
                $ap->middles++;
                break;
            case 'finish';
                $ap->finishes++;
                $ap->endingTasks[] = $task;
                break;
            case 'single';
                $ap->singles++;
                $ap->singleTasks[] = $task;
                break;
        }

        return $ap;
    }
}

class MountPoint
{
    public $x, $y;

    static $_nextId = 0;

    public function __construct($row)
    {
        $this->id = static::$_nextId++;
        [$x, $y] = explode(" ", $row);
        $this->x = (int)$x;
        $this->y = (int)$y;
    }
}

class Task
{
    public $id;
    /** @var int $score */
    public $score;
    /** @var int $nAssemblyPoints */
    public $nAssemblyPoints;
    /** @var int $nSteps */
    public $nSteps = 0;
    /** @var int $scorePerStep */
    public $scorePerStep;
    /** @var int $offsettedScorePerStep */
    public $offsettedScorePerStep;
    /** @var array[AssemblyPoint] $assemblyPoints */
    public $assemblyPoints = []; // AssemblyPoints
    public $startX, $startY, $endX, $endY;

    public static $lastId = 0;

    public function __construct($row1, $row2)
    {
        $this->id = self::$lastId++;

        [$score, $assemblyPoints] = explode(" ", $row1);
        $score = (int)$score;
        $nAssemblyPoints = (int)$assemblyPoints;
        $this->nAssemblyPoints = $nAssemblyPoints;
        $this->score = $score;
        $this->assemblyPoints = [];
        $c = 0;
        $status = 'single';
        $row2 = explode(" ", $row2);
        $prevX = null;
        $prevY = null;
        for ($i = 0; $i < $nAssemblyPoints; $i++) {
            $x = (int)$row2[$c++];
            $y = (int)$row2[$c++];
            if ($i == 0) {
                $status = 'start';
                if ($nAssemblyPoints == 1) {
                    $status = 'single';
                    $this->endX = $x;
                    $this->endY = $y;
                }
                $this->startX = $x;
                $this->startY = $y;
            } elseif ($i == $nAssemblyPoints - 1) {
                $status = 'finish';
                $this->endX = $x;
                $this->endY = $y;
            } else {
                $status = 'middle';
            }

            if ($status != 'start' && $status != 'single') {
                $this->nSteps += abs($x - $prevX) + abs($y - $prevY);
            }
            $prevX = $x;
            $prevY = $y;

            $this->assemblyPoints[] = AssemblyPoint::get($x, $y, $status, $this);
        }
        $this->scorePerStep = $this->score / ($this->nSteps + 1); //+1 to fix the single cases
        $this->offsettedScorePerStep = $this->score / ($this->nSteps + 10); //+10 to assume there are X steps to do, to reach the point
    }
}

class Arm
{
    /** @var MountPoint $mountPoint */
    public $mountPoint;

    /** @var Cell $start */
    public $start;

    /** @var Cell[] $nodes */
    public $nodes = [];

    /** @var Task $currentTask */
    public $currentTask;

    /** @var array $schedule */
    public $schedule;
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
[$W, $H, $N_ARMS, $N_MOUNT_POINTS, $N_TASKS, $N_STEPS] = explode(" ", $content[0]);
$W = (int)$W;
$H = (int)$H;
$N_ARMS = (int)$N_ARMS;
$N_MOUNT_POINTS = (int)$N_MOUNT_POINTS;
$N_TASKS = (int)$N_TASKS;
$N_STEPS = (int)$N_STEPS;

$r = 1;

for ($x = 0; $x < $W; $x++) {
    for ($y = 0; $y < $H; $y++) {
        $cell = new Cell($x, $y);
        $CELLS[] = $cell;
        $MAP[$x][$y] = $cell;
    }
}

for ($i = 0; $i < $N_MOUNT_POINTS; $i++) {
    $mountPoint = new MountPoint($content[$r++]);
    $MOUNT_POINTS[$mountPoint->id] = $mountPoint;
    $MAP[$mountPoint->x][$mountPoint->y] = $mountPoint;
}

for ($i = 0; $i < $N_TASKS; $i++) {
    $task = new Task($content[$r++], $content[$r++]);
    $TASKS[$task->id] = $task;
}

$N_MOUNT_POINTS = count($MOUNT_POINTS);
$N_TASKS = count($TASKS);
$N_ASSEMBLY_POINTS = count($ASSEMBLY_POINTS);

