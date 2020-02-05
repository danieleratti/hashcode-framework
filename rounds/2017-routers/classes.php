<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'b';

require_once './reader.php';

function getGridCellColor($cell)
{
    switch ($cell) {
        case '-':
            return Colors::white;

        case '.':
            return Colors::red0;
        case '#':
            return Colors::black;
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

    private $routers;
    private $backbones;
    private $covered;

    private $fileManager;
    private $visualizer;
    private $remainingBudget;

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

        $this->remainingBudget = $budget;
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

    public function placeRouter($row, $col)
    {
        if ($this->grid[$row][$col] != '.')
            die("Hai piazzato un router in un posto di merda ($row, $col)");
        if (isset($this->routers[$row][$col]))
            die("Hai piazzato un router sopra un altro ($row, $col)");

        $this->routers[$row][$col] = true;

        $bRow = $this->backboneRow;
        $bCol = $this->backboneCol;

        while ($bRow != $row || $bCol != $col) {
            $bRow += min(max($row - $bRow, -1), 1);
            $bCol += min(max($col - $bCol, -1), 1);
            $this->backbones[$bRow][$bCol] = true;
        }

        $this->remainingBudget -= $this->getBackboneCost($row, $col);
        $this->remainingBudget -= $this->routerCosts;
    }

    public function getBackboneCost($row, $col)
    {
        return max(abs($this->backboneRow - $row), $deltaC = abs($this->backboneCol - $col));
    }

    public function printSolution()
    {
        foreach ($this->grid as $row => $cellsRow) {
            foreach ($cellsRow as $col => $cell) {
                $this->visualizer->setPixel($row, $col, getGridCellColor($cell));

                if (isset($this->covered[$row][$col]))
                    $this->visualizer->setPixel($row, $col, Colors::green5);
                if (isset($this->backbones[$row][$col]))
                    $this->visualizer->setPixel($row, $col, Colors::purple1);
                if (isset($this->routers[$row][$col]))
                    $this->visualizer->setPixel($row, $col, Colors::green7);
            }
        }
        $this->visualizer->setPixel($this->backboneRow, $this->backboneCol, Colors::purple5);

        $this->visualizer->save('solution_' . $this->fileManager->getInputName());
    }

    public function outputSolution()
    {
        $backbones = [];
        $routers = [];
        for ($r = 0; $r < $this->gridRows; $r++) {
            for ($c = 0; $c < $this->gridCols; $c++) {
                if (isset($this->backbones[$r][$c]))
                    $backbones[] = "$r $c";
                if (isset($this->routers[$r][$c]))
                    $routers[] = "$r $c";
            }
        }

        $output = count($backbones) . "\n";
        $output .= implode("\n", $backbones);
        $output .= "\n" . count($routers) . "\n";
        $output .= implode("\n", $routers);

        $this->fileManager->output($output);
    }
}

$grid = new Grid();
$grid->placeRouter(36, 62);
$grid->printSolution();
$grid->outputSolution();
