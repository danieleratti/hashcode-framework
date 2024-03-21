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
        $adjacentNodes = [
            [$r - 1, $c],
            [$r + 1, $c],
            [$r, $c - 1],
            [$r, $c + 1],
        ];
        return $node;
    }

    public function calculateRealCost(mixed $node, mixed $adjacent): float|int
    {
        // Return the actual cost between two adjacent nodes
    }

    public function calculateEstimatedCost(mixed $fromNode, mixed $toNode): float|int
    {
        // Return the heuristic estimated cost between the two given nodes
    }

    // ...
}