<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

require_once './reader.php';

function getGridCellColor($cell)
{
    switch ($cell) {
        case '-':
            return Colors::black;

        case '.':
            return Colors::green2;
        case '#':
            return Colors::red3;
        default:
            die("Stai facendo una stronzata zio");
    }
}

class Grid
{
    public $gridRows;
    public $gridCols;
    public $routerRange;
    public $backboneCosts;
    public $routerCosts;
    public $budget;
    public $backboneRow;
    public $backboneCol;
    public $grid;

    private $fileManager;
    private $visualizer;

    public function __construct()
    {
        global
        $gridRows,
        $gridCols,
        $routerRange,
        $backboneCosts,
        $routerCosts,
        $budget,
        $backboneRow,
        $backboneCol,
        $gridArray,
        $fileManager;

        $this->gridRows = $gridRows;
        $this->gridCols = $gridCols;
        $this->routerRange = $routerRange;
        $this->backboneCosts = $backboneCosts;
        $this->routerCosts = $routerCosts;
        $this->budget = $budget;
        $this->backboneRow = $backboneRow;
        $this->backboneCol = $backboneCol;
        $this->grid = $gridArray;
        $this->fileManager = $fileManager;

        $this->visualizer = new VisualStandard($gridRows, $gridCols);
    }

    public function visualizeEmptyGrid()
    {
        foreach ($this->grid as $row => $cellsRow) {
            foreach ($cellsRow as $col => $cell) {
                $this->visualizer->setPixel($row, $col, getGridCellColor($cell));
            }
        }
        $this->visualizer->save($this->fileManager->getInputName());
    }
}

$grid = new Grid();

$grid->visualizeEmptyGrid();
