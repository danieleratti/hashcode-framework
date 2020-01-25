<?php

use Utils\Chart;

require_once '../../bootstrap.php';

$chart = new Chart('test');
/*
$histogramData = [];
for ($i = 0; $i < 100; $i++) {
    $histogramData[] = [
        $i,
        $i
    ];
}
$chart->plotHistogram2D($histogramData);
*/

/*
$histogramData = [];
for ($i = 0; $i < 1000; $i++) {
    $histogramData[] = rand(1, 100) < 50 ? rand(1, 100) : rand(50, 100);
}
$chart->plotHistogram($histogramData);
*/

//$chart->plotLineXY([[1,1], [2,2], [3,3], [5,10]]);
//$chart->plotLineY([1,2,3,4,3,2,1]);
//$chart->plotPoints([[1,1], [2,2], [3,3], [5,10]]);
//$chart->plotBubbles([[1, 1, 1, 'red', '1 small red'], [2, 2, 2, 'blue', '2 medium blue'], [3, 3, 3, 'green', '3 big green']]);
$chart->plotPoints3D([[1,1,1], [2,2,2], [3,3,3], [5,10,5]]);
