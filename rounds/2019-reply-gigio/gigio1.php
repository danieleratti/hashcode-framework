<?php

$fileName = '0';

require_once 'reader.php';

$revenues = [];

/** @var GMap $map */
foreach ($map->cells as $row) {
    foreach ($row as $cell) {
        $res = $cell->getPositiveRevenuesSum();
        $revenues[] = [
            'row' => $cell->row,
            'col' => $cell->col,
            'clients' => $res['clients'],
            'total' => $res['total'],
        ];
    }
}

$sol = collect($revenues)
    ->sortByDesc('total')
    ->take($maxOfficesCount);

$map->outputSolution($sol);
