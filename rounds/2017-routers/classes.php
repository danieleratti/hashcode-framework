<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

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
    private $backbonesPath;
    private $covered;

    private $fileManager;
    private $visualizer;
    public $remainingBudget;

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

        $this->backbonesPath = [];
        $this->remainingBudget = $budget;
        $this->visualizer = new VisualStandard($gridRows, $gridCols);
    }

    public function placeRouter($row, $col)
    {
        if ($this->grid[$row][$col] != '.') {
            echo "Hai piazzato un router in un posto di merda ($row, $col)\n";
            return false;
        }
        if (isset($this->routers[$row][$col])) {
            echo "Hai piazzato un router sopra un altro ($row, $col)\n";
            return false;
        }

        if ($this->remainingBudget - $this->getBackboneCost($row, $col) - $this->routerCosts < 0) {
            echo "Budget insufficiente, non piazzo il router ($row, $col)\n";
            return false;
        }


        $this->routers[$row][$col] = true;

        $bRow = $this->backboneRow;
        $bCol = $this->backboneCol;

        $initialBackbones = count($this->backbonesPath);
        while ($bRow != $row || $bCol != $col) {
            $bRow += min(max($row - $bRow, -1), 1);
            $bCol += min(max($col - $bCol, -1), 1);
            $this->backbones[$bRow][$bCol] = true;
            $this->backbonesPath[] = [$bRow, $bCol];
        }

        $covered = $this->getCoveredCells($row, $col);
        foreach ($covered as $coveredCell)
            $this->covered[$coveredCell[0]][$coveredCell[1]] = true;

        $cost = $this->routerCosts + (count($this->backbonesPath) - $initialBackbones) * $this->backboneCosts;
        $this->remainingBudget -= $cost;

        return true;
    }

    public function getCoveredCells($row, $col)
    {
        $result = [];
        for ($r = max(0, $row - $this->routerRange); $r <= min($this->gridRows, $row + $this->routerRange); $r++) {
            for ($c = max(0, $col - $this->routerRange); $c <= min($this->gridCols, $col + $this->routerRange); $c++) {
                if (isThereAWall($this->grid, $r, $c, $row, $col))
                    continue;
                $result[] = [$r, $c];
            }
        }
        return $result;
    }

    public function getValidCoveredCells($row, $col)
    {
        $result = [];
        for ($r = max(0, $row - $this->routerRange); $r <= min($this->gridRows, $row + $this->routerRange); $r++) {
            for ($c = max(0, $col - $this->routerRange); $c <= min($this->gridCols, $col + $this->routerRange); $c++) {
                if ($this->covered[$r][$c])
                    continue;
                if (isThereAWall($this->grid, $r, $c, $row, $col))
                    continue;
                $result[] = [$r, $c];
            }
        }
        return $result;
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

                if ($this->covered[$row][$col])
                    $this->visualizer->setPixel($row, $col, Colors::green2);
                if (isset($this->backbones[$row][$col]))
                    $this->visualizer->setPixel($row, $col, Colors::purple1);
                if (isset($this->routers[$row][$col]))
                    $this->visualizer->setPixel($row, $col, Colors::green7);
            }
        }
        $this->visualizer->setPixel($this->backboneRow, $this->backboneCol, Colors::purple5);

        $this->visualizer->save($this->fileManager->getInputName());
    }

    public function outputSolution()
    {
        $routers = [];
        $coveredCount = 0;
        for ($r = 0; $r < $this->gridRows; $r++) {
            for ($c = 0; $c < $this->gridCols; $c++) {
                if (isset($this->routers[$r][$c]))
                    $routers[] = "$r $c";
                if ($this->covered[$r][$c])
                    $coveredCount++;
            }
        }

        $serialized = array_map('serialize', $this->backbonesPath);
        $unique = array_unique($serialized);
        $backbones = array_intersect_key($this->backbonesPath, $unique);

        $output = count($backbones) . "\n";
        foreach ($backbones as $backbone) {
            $output .= "$backbone[0] $backbone[1]\n";
        }
        $output  = substr($output, 0, -1);
        $output .= "\n" . count($routers) . "\n";
        $output .= implode("\n", $routers);

        $costs = (count($backbones) * $this->backboneCosts) + (count($routers) * $this->routerCosts);
        $budget = $this->budget;
        $revenue = $coveredCount * 1000;
        $score = $revenue + $budget - $costs;

        echo "BUDGET: $budget\n";
        echo "COSTO: $costs\n";
        echo "COVERED: $coveredCount\n";
        echo "SCORE: $score";

        $this->fileManager->output($output);
    }
}
