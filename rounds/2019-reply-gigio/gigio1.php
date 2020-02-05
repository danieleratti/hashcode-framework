<?php

$fileName = '2';

require_once 'reader.php';

$results = [];

/** @var GMap $map */
foreach ($map->cells as $row) {
    foreach ($row as $cell) {
        $res = $cell->getRevenuesSum();

        if (!count($res['positiveClients']))
            continue;

        $results[] = [
            'row' => $cell->row,
            'col' => $cell->col,
            'clients' => $res['positiveClients'],
            'score' => $res['positiveRevenues'],
        ];
    }
}

$sol = collect($results)
    ->sortByDesc('score')
    ->take($maxOfficesCount);

$map->outputSolution($sol);
