<?php

use Utils\Chart;
use Utils\Collection;

$fileName = 'a';

include __DIR__ . '/reader.php';

$chart = new Chart('percentileChunkedScore_' . $fileName);
$chart->plotMultiLineY([
    //['name' => 'percentileChunkScore', 'line' => $libraries->sortBy('percentileChunkScore')->pluck('percentileChunkScore')->toArray()],
    //['name' => 'booksChunkedScore', 'line' => $libraries->sortBy('librariesConnectedCount')->pluck('booksChunkedScore')->toArray(), 'custom_axis' => true, 'side' => 'right'],
]);
