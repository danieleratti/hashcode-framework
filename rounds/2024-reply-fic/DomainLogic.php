<?php

use JMGQ\AStar\DomainLogicInterface;

class DomainLogic implements DomainLogicInterface
{
    public MapManager $mapManager;

    public function __construct(MapManager $mapManager)
    {
        $this->mapManager = $mapManager;
    }

    public function getAdjacentNodes(mixed $node): iterable
    {
        [$r, $c] = $node;
        $adjacentNodes = [];
        if ($r > 0) {
            $adjacentNodes[] = [$r - 1, $c];
        }
        if ($r < $this->mapManager->rowsCount - 1) {
            $adjacentNodes[] = [$r + 1, $c];
        }
        if ($c > 0) {
            $adjacentNodes[] = [$r, $c - 1];
        }
        if ($c < $this->mapManager->columnsCount - 1) {
            $adjacentNodes[] = [$r, $c + 1];
        }

        return $adjacentNodes;
    }

    public function calculateRealCost(mixed $node, mixed $adjacent): float|int
    {
        return 1;
    }

    public function calculateEstimatedCost(mixed $fromNode, mixed $toNode): float|int
    {
        [$r1, $c1] = $fromNode;
        [$r2, $c2] = $toNode;
        return abs($r1 - $r2) + abs($c1 - $c2);
    }

    // ...
}