<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

/**
 * @param Building $build1
 * @param int $r1
 * @param int $c1
 * @param Building $build2
 * @param int $r2
 * @param int $c2
 * @return int
 */
function calculateDistance($build1, $r1, $c1, $build2, $r2, $c2)
{
    $perimeter1 = $build1->getRelativePerimeter($r1, $c1);
    $perimeter2 = $build2->getRelativePerimeter($r2, $c2);

    $minDistance = null;
    foreach ($perimeter1 as $pcell1) {
        foreach ($perimeter2 as $pcell2) {
            $distance = abs($pcell1[0] - $pcell2[0]) + abs($pcell1[1] - $pcell2[1]);
            if (is_null($minDistance) || $distance < $minDistance)
                $minDistance = $distance;
        }
    }

    return $minDistance;
}

/**
 * @param Building $build1
 * @param int $r1
 * @param int $c1
 * @param Building $build2
 * @param int $r2
 * @param int $c2
 * @return int
 */
function collide($build1, $r1, $c1, $build2, $r2, $c2)
{
    $top1 = $r1;
    $bottom1 = $r1 + $build1->height;
    $left1 = $c1;
    $right1 = $c1 + $build1->width;

    $top2 = $r2;
    $bottom2 = $r2 + $build2->height;
    $left2 = $c2;
    $right2 = $c2 + $build2->width;

    if (
        $bottom1 < $top2
        || $right1 < $left2
        || $bottom2 < $top1
        || $right2 < $left1
    )
        return false;

    $cells1 = $build1->getRelativeCellsList($r1, $c1);
    $cells2 = $build2->getRelativeCellsList($r2, $c2);

    foreach ($cells1 as $cell1) {
        foreach ($cells2 as $cell2) {
            if ($cell1[0] == $cell2[0] && $cell1[1] == $cell2[1])
                return true;
        }
    }
    return false;
}

class Building
{
    /** @var int $id */
    public $id;
    /** @var bool[][] $plan */
    public $plan;
    /** @var string $buildingType */
    public $buildingType;
    /** @var bool[] $perimeter */
    public $perimeter;
    /** @var int $height */
    public $height;
    /** @var int $width */
    public $width;

    private $_stringPlan;

    public function __construct($id, $plan, $buildingType)
    {
        $this->id = $id;
        $this->buildingType = $buildingType;
        $this->_stringPlan = $plan;

        $booleanPlan = [];
        foreach ($plan as $row => $planRow) {
            foreach (str_split($planRow, 1) as $col => $planCell) {
                $cell = $planCell == '#';
                $booleanPlan[$row][$col] = $cell;
            }
        }

        $height = count($booleanPlan);
        $width = count($booleanPlan[0]);

        foreach ($booleanPlan as $row => $planRow) {
            foreach ($planRow as $col => $cell) {
                if (!$cell)
                    continue;
                if (
                    $row == 0
                    || $row == ($height - 1)
                    || $col == 0
                    || $col == ($width - 1)
                    || !$booleanPlan[$row + 1][$col]
                    || !$booleanPlan[$row - 1][$col]
                    || !$booleanPlan[$row][$col + 1]
                    || !$booleanPlan[$row][$col - 1]
                ) {
                    $this->perimeter[] = [$row, $col];
                }
            }
        }

        $this->width = $width;
        $this->height = $height;
        $this->plan = $booleanPlan;
    }

    public function getRelativePerimeter($r, $c)
    {
        $relativePerimeter = [];
        foreach ($this->perimeter as $cell) {
            $relativePerimeter[] = [($cell[0] + $r), ($cell[1] + $c)];
        }
        return $relativePerimeter;
    }

    public function getRelativeCellsList($r, $c)
    {
        $list = [];
        foreach ($this->getCellsList() as $cell) {
            $list[] = [($cell[0] + $r), ($cell[1] + $c)];
        }
        return $list;
    }

    public function getCellsList()
    {
        if (is_null($this->cellsList)) {
            $this->cellsList = [];
            foreach ($this->plan as $row => $planRow) {
                foreach ($planRow as $col => $cell) {
                    if (!$cell)
                        continue;
                    $this->cellsList = [$row, $col];
                }
            }
        }

        return $this->cellsList;
    }
}

class Residence extends Building
{
    /** @var int $capacity */
    public $capacity;

    /** @var double $efficiency */
    public $efficiency;    // capacity / area

    public function __construct($id, $plan, $capacity)
    {
        parent::__construct($id, $plan, 'R');
        $this->capacity = $capacity;
        $this->efficiency = $capacity / $this->width / $this->height;
    }
}

class Utility extends Building
{
    /** @var int $utilityType */
    public $utilityType;

    public function __construct($id, $plan, $type)
    {
        parent::__construct($id, $plan, 'U');
        $this->utilityType = $type;
    }
}

class City
{
    public $rows;
    public $cols;

    public $placedBuildings;
    public $map;

    public function __construct($rows, $cols)
    {
        $this->rows = $rows;
        $this->cols = $cols;
        $this->placedBuildings = collect();
    }

    /**
     * @param Building $building
     * @param $row
     * @param $col
     * @param bool $check
     * @return bool
     */
    public function placeBuilding($building, $row, $col, $check = true)
    {
        if ($check) {
            $this->canPlace($building, $row, $col);
        }

        $this->placedBuildings->add([
            "type" => $building->buildingType,
            "r" => $row,
            "c" => $col,
            "building" => $building,
        ]);

        $relativeCells = $building->getRelativeCellsList($row, $col);
        foreach ($relativeCells as $cell) {
            $this->map[$cell[0]][$cell[1]] = $building->id;
        }

        return true;
    }

    /**
     * @param Building $building
     * @param $row
     * @param $col
     * @return bool
     */
    public function canPlace($building, $row, $col)
    {
        $relativeCells = $building->getRelativeCellsList($row, $col);

        foreach ($relativeCells as $cell) {
            if (
                $cell[0] < 0
                || $cell[0] >= $this->rows
                || $cell[1] < 0
                || $cell[1] >= $this->cols
                || !is_null($this->map[$cell[0]][$cell[1]])
            )
                return false;
        }

        return true;
    }

    public function getScore()
    {
        global $maxWalkingDistance;

        $residences = $this->placedBuildings->where('type', '=', 'R');
        $utilities = $this->placedBuildings->where('type', '=', 'U');

        $score = 0;
        foreach ($residences as $placedR) {
            /** @var Residence $residence */
            $residence = $placedR['building'];
            foreach ($utilities as $placedU) {
                /** @var Utility $utility */
                $utility = $placedU['building'];
                $distance = calculateDistance($residence, $placedR['r'], $placedR['c'], $utility, $placedU['r'], $placedU['c']);

                if ($distance > $maxWalkingDistance)
                    continue;

                $score += $residence->capacity;
            }
        }

        return $score;
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$cityRows, $cityColumns, $maxWalkingDistance, $buildingPlansCount] = explode(' ', $content[0]);

$city = new City($cityRows, $cityColumns);

$buildings = collect();

$fileRow = 1;
$id = 0;
while ($fileRow < count($content)) {
    [$projectType, $rows, $columns, $data] = explode(' ', $content[$fileRow]);
    $fileRow++;
    $plan = array_slice($content, $fileRow, $rows);
    if ($projectType == 'U')
        $buildings->add(new Utility($id, $plan, $data));
    else
        $buildings->add(new Residence($id, $plan, $data));
    $fileRow += $rows;
    $id++;
}

$residences = $buildings->where('buildingType', 'R');
$utilities = $buildings->where('buildingType', 'U');
