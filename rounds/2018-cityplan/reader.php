<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Building
{
    /** @var int $id */
    public $id;
    /** @var string[][] $plan */
    public $plan;
    /** @var string $buildingType */
    public $buildingType;

    private $_stringPlan;
    public $perimeter;

    public $height;
    public $width;

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
                if ($row == 0 || $row == ($height - 1) || $col == 0 || $col == ($width - 1))
                    $this->perimeter = 0;
            }
        }

        $this->width = $width;
        $this->height = $height;
        $this->plan = $booleanPlan;
    }
}

class Residence extends Building
{
    /** @var int $capacity */
    public $capacity;

    public function __construct($id, $plan, $capacity)
    {
        parent::__construct($id, $plan, 'R');
        $this->capacity = $capacity;
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

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($cityRows, $cityColumns, $maxWalkingDistance, $buildingPlansCount) = explode(' ', $content[0]);

$buildings = collect();

$fileRow = 1;
$id = 0;
while ($fileRow < count($content)) {
    list($projectType, $rows, $columns, $data) = explode(' ', $content[$fileRow]);
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
