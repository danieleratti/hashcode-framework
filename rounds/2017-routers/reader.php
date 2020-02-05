<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Cell
{
    public $r;
    public $c;
    public $type;

    public function __construct($r, $c, $type)
    {
        $this->r = $r;
        $this->c = $c;
        $this->type = $type;
    }

    public function isTarget()
    {
        return $this->type === '.';
    }

    public function isWall()
    {
        return $this->type === '#';
    }

    public function isVoid()
    {
        return $this->type === '-';
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$fileContent = $fileManager->get();

$data = explode("\n", $fileContent);
unset($fileContent);
[$rowsCount, $columnsCount, $routerRadius] = explode(' ', $data[0]);
[$backbonePrice, $routerPrice, $maxBudget] = explode(' ', $data[1]);
[$backboneStartRow, $backboneStartColumn] = explode(' ', $data[2]);
array_splice($data, 0, 3);

$rowsCount = (int)$rowsCount;
$columnsCount = (int)$columnsCount;
$routerRadius = (int)$routerRadius;

$backbonePrice = (int)$backbonePrice;
$routerPrice = (int)$routerPrice;
$maxBudget = (int)$maxBudget;

$backboneStartRow = (int)$backboneStartRow;
$backboneStartColumn = (int)$backboneStartColumn;

/** @var Cell[][] $map */
$map = [];

for ($i = 0; $i < $rowsCount; $i++) {
    $map[$i] = [];
    for ($k = 0; $k < $columnsCount; $k++) {
        $map[$i][$k] = new Cell($i, $k, $data[$i][$k]);
    }
}

unset($data);
