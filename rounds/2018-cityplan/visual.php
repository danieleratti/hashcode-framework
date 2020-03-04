<?php

use Utils\Chart;
use Utils\Collection;

$fileName = 'a';

include 'reader.php';

$chart = new Chart('xxx' . $fileName);
$chart->plotMultiLineY([
    ['name' => 'SignupDuration', 'line' => $libraries->sortBy('signUpDuration')->pluck('signUpDuration')->toArray()],
    ['name' => 'booksChunkedScore', 'line' => $libraries->sortBy('signUpDuration')->pluck('booksChunkedScore')->toArray(), 'custom_axis' => true, 'side' => 'right']
]);
echo $buildings->count();
