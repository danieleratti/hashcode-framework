<?php

$distanceCache = [];

function distanceBetween(int $startRow, int $startColumn, int $finishRow, int $finishColumn): int
{
    // Try cache
    return abs($startRow - $finishRow) + abs($startColumn - $finishColumn);
}
