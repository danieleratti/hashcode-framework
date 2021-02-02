<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;

$fileName = $fileName ?: 'b';

$W = 0;
$H = 0;
$N_MOUNT_POINTS = 0;
$N_ASSEMBLY_POINTS = 0;
$N_TASKS = 0;
$N_ARMS = 0;
$N_STEPS = 0;

//$MAP = []; // [x][y]=m (mounting point),a (assembly point)
$ASSEMBLY_POINTS = collect();
$MOUNT_POINTS = collect();
$TASKS = collect();
$ARMS = collect();

class AssemblyPoint
{
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

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public static function get($x, $y, $status, $task)
    {
        global $ASSEMBLY_POINTS;
        if ($ap = $ASSEMBLY_POINTS->where('x', $x)->where('y', $y)->first()) {
            /** @var AssemblyPoint $ap */
        } else {
            $ap = new AssemblyPoint($x, $y);
            $ASSEMBLY_POINTS->add($ap);
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
    public $id;
    public $x, $y;

    public function __construct($id, $row)
    {
        $this->id = $id;
        list($x, $y) = explode(" ", $row);
        $this->x = (int)$x;
        $this->y = (int)$y;
    }
}

class Arm
{
    public $id;
    /** @var int $x */
    public $x;
    /** @var int $y */
    public $y;
    /** @var string $scorePerStep */
    public $path;
    /** @var Task[] $tasks */
    public $tasks;
    /** @var Task $task */
    public $currentTask;
    /** @var MountPoint $mountPoint */
    public $mountPoint;

    public function __construct($id)
    {
        $this->id = $id;
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
    /** @var array[AssemblyPoint] $assemblyPoints */
    public $assemblyPoints = []; // AssemblyPoints
    public $startX, $startY, $endX, $endY;

    public static $lastId = 0;

    public function __construct($row1, $row2)
    {
        $this->id = self::$lastId++;

        list($score, $assemblyPoints) = explode(" ", $row1);
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
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
list($W, $H, $N_ARMS, $N_MOUNT_POINTS, $N_TASKS, $N_STEPS) = explode(" ", $content[0]);
$W = (int)$W;
$H = (int)$H;
$N_ARMS = (int)$N_ARMS;
$N_MOUNT_POINTS = (int)$N_MOUNT_POINTS;
$N_TASKS = (int)$N_TASKS;
$N_STEPS = (int)$N_STEPS;

for ($i = 0; $i < $N_ARMS; $i++)
    $ARMS->add(new Arm($i));

$r = 1;

for ($i = 0; $i < $N_MOUNT_POINTS; $i++)
    $MOUNT_POINTS->add(new MountPoint($i, $content[$r++]));
$MOUNT_POINTS->keyBy('id');

for ($i = 0; $i < $N_TASKS; $i++)
    $TASKS->add(new Task($content[$r++], $content[$r++]));
$TASKS->keyBy('id');

$N_MOUNT_POINTS = $MOUNT_POINTS->count();
$N_TASKS = $TASKS->count();
$N_ASSEMBLY_POINTS = $ASSEMBLY_POINTS->count();

$mappaDiProva = [
    [0, 1, 0, 0],
    [0, 0, 0, 0],
    [1, 1, 1, 0],
    [0, 0, 0, 0],
];

print_r(findPath($mappaDiProva, 4, 4, [0, 0], [3, 0]));

function findPath($map, $maxR, $maxC, $start, $end)
{
    $edges = [
        [
            'cord' => $start,
            'path' => ''
        ]
    ];

    $checked[$start[0]][$start[1]] = true;
    $solutions = [];

    $isFreeEdge = function ($pos) use (&$checked, $map, $maxR, $maxC) {
        if (
            $pos[0] < 0 ||
            $pos[0] >= $maxR ||
            $pos[1] < 0 ||
            $pos[1] >= $maxC ||
            $map[$pos[0]][$pos[1]] == 1 ||
            $checked[$pos[0]][$pos[1]] == true
        )
            return false;
        return true;
    };

    while (count($solutions) === 0 && count($edges) > 0) {
        $nextEdges = [];
        foreach ($edges as $edge) {
            $edgeCord = $edge['cord'];
            $nearPoints = [
                [
                    'deltaPath' => 'U',
                    'cord' => [$edgeCord[0] - 1, $edgeCord[1]]
                ],
                [
                    'deltaPath' => 'D',
                    'cord' => [$edgeCord[0] + 1, $edgeCord[1]]
                ],
                [
                    'deltaPath' => 'L',
                    'cord' => [$edgeCord[0], $edgeCord[1] - 1]
                ],
                [
                    'deltaPath' => 'R',
                    'cord' => [$edgeCord[0], $edgeCord[1] + 1]
                ],
            ];

            foreach ($nearPoints as $nearPoint) {
                $cord = $nearPoint['cord'];
                if (!$isFreeEdge($cord))
                    continue;

                $checked[$cord[0]][$cord[1]] = true;
                $newEdge = [
                    'cord' => $cord,
                    'path' => $edge['path'] . $nearPoint['deltaPath'],
                ];

                $nextEdges[] = $newEdge;
                if ($cord[0] === $end[0] && $cord[1] === $end[1])
                    $solutions[] = $newEdge['path'];
            }
        }

        $edges = $nextEdges;
    }

    return $solutions;
}
