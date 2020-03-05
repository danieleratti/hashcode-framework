<?php

use Utils\FileManager;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

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

    private $cellsList;

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

    public function __toString()
    {
        return implode("\n", $this->_stringPlan);
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
                    $this->cellsList[] = [$row, $col];
                }
            }
        }

        return $this->cellsList;
    }

    public function getWalkableArea()
    {
        global $maxWalkingDistance;

        $founded = [];
        foreach ($this->perimeter as $cell) {
            for ($r = -$maxWalkingDistance; $r <= $maxWalkingDistance; $r++) {
                for ($c = abs($r) - $maxWalkingDistance; $c <= $maxWalkingDistance - abs($r); $c++) {
                    $founded[($r + $cell[0]) . "-" . ($c + $cell[1])] = true;
                }
            }
        }
        $area = [];
        foreach ($founded as $rc => $null) {
            $area[] = explode("-", $rc);
        }

        return $area;
    }

    public function getRelativeWalkableArea($r, $c)
    {
        $area = [];
        foreach ($this->getWalkableArea() as $cell) {
            $area[] = [$r + $cell[0], $c + $cell[1]];
        }
        return $area;
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
        global $maxWalkingDistance, $utilities;

        $placedResidences = $this->placedBuildings->where('type', '=', 'R');

        $score = 0;
        foreach ($placedResidences as $placedR) {
            $foundedBuildings = [];
            /** @var Residence $residence */
            $residence = $placedR['building'];
            foreach ($residence->getRelativeWalkableArea($placedR['r'], $placedR['c']) as $cell) {
                $foundedBuildings[$this->map[$cell[0]][$cell[1]]] = true;
            }

            $utilitiesType = [];
            foreach ($foundedBuildings as $id => $null) {
                if (!$id)
                    continue;

                $build = $utilities->get($id);
                if ($build->buildingType != 'U')
                    continue;
                $utilitiesType[$build->utilityType] = true;
            }

            $score += count($utilitiesType) * $residence->capacity;
        }

        return $score;
    }

    public function print()
    {
        global $fileName;
        $visualStandard = new VisualStandard($this->rows, $this->cols);

        $utilityColors = [
            //Colors::blue0,
            Colors::blue1,
            Colors::blue2,
            Colors::blue3,
            Colors::blue4,
            Colors::blue5,
            Colors::blue6,
            Colors::blue7,
            Colors::blue8,
            Colors::blue9,
        ];
        $utilityIdx = 0;

        $residenceColors = [
            //Colors::red0,
            Colors::red1,
            Colors::red2,
            Colors::red3,
            Colors::red4,
            Colors::red5,
            Colors::red6,
            Colors::red7,
            Colors::red8,
            Colors::red9,
        ];
        $residenceIdx = 0;

        foreach ($this->placedBuildings as $placedBuilding) {
            /** @var Building $building */
            $building = $placedBuilding['building'];

            $color = $building instanceof Utility ? $utilityColors[($utilityIdx++) % count($utilityColors)] : $residenceColors[($residenceIdx++) % count($residenceColors)];

            $cells = $building->getRelativeCellsList($placedBuilding['r'], $placedBuilding['c']);
            foreach ($cells as $cell) {
                $visualStandard->setPixel($cell[0], $cell[1], $color);
            }
        }
        $visualStandard->save('city_' . $fileName);
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

$buildings = $buildings->keyBy('id');
$residences = $buildings->where('buildingType', 'R')->keyBy('id');
$utilities = $buildings->where('buildingType', 'U')->keyBy('id');
