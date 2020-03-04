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

    public function __construct($id, $plan, $buildingType)
    {
        $this->id = $id;
        $this->plan = $plan;
        $this->buildingType = $buildingType;
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
