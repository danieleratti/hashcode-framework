<?php

use Utils\FileManager;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

require_once '../../bootstrap.php';

class Cell
{
    /** @var int $r */
    public $r;
    /** @var int $c */
    public $c;
    /** @var bool $isTarget */
    public $isTarget;
    /** @var bool $isWall */
    public $isWall;
    /** @var bool $isVoid */
    public $isVoid;
    /** @var bool $isCovered */
    public $isCovered = false;
    /** @var bool $hasRouter */
    public $hasRouter = false;
    /** @var bool $hasBackbone */
    public $hasBackbone = false;
    /** @var array $coverableCells */
    public $coverableCells = [];

    public function __construct($r, $c, $type)
    {
        $this->r = $r;
        $this->c = $c;
        $this->isTarget = $type === '.';
        $this->isWall = $type === '#';
        $this->isVoid = $type === '-';
    }
}

// Functions
function plot($name = '')
{
    global $fileName, $rowsCount, $columnsCount, $map;
    $visualStandard = new VisualStandard($rowsCount, $columnsCount);
    for ($row = 0; $row < $rowsCount; $row++) {
        for ($col = 0; $col < $columnsCount; $col++) {
            /** @var Cell $cell */
            $cell = $map[$row][$col];

            $color = Colors::black;

            if ($cell->hasRouter)
                $color = Colors::pink4;
            elseif ($cell->hasBackbone)
                $color = Colors::orange7;
            elseif ($cell->isTarget) {
                if ($cell->isCovered)
                    $color = Colors::green9;
                else
                    $color = Colors::green0;
            } elseif ($cell->isWall)
                $color = Colors::brown5;

            $visualStandard->setPixel($row, $col, $color);
        }
    }
    $visualStandard->save($fileName . ($name != '' ? ('_' . $name) : ''));
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

$map[$backboneStartRow][$backboneStartColumn]->hasBackbone = true;

unset($data);
