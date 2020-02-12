<?php

$fileName = 'a';
$outputName = 'a.txt';

include 'reader.php';

$content = trim(file_get_contents(__DIR__ . '/output/' . $outputName));
$rows = explode("\n", $content);

/*
$points = 0;
foreach ($rows as $row) {
    $outRides = explode(' ', $row);
    array_shift($outRides);

    $car = new Car(0);
    foreach ($outRides as $ride) {
        if (!isset($rides[$ride]))
            die('ride usata due volte');

        $points += $car->takeRide($rides->get($ride));
    }
}
*/
$score = 0;
array_shift($rows);
foreach ($arr as $a){
    $file = $files[$a[0]];
    //$server = $a[1];
    if(in_array($file->filename, array_keys($targetFiles))){
        if($file->timeCompilation <= $file->deadLine){
            $score += $file->deadLine - $file->timeCompilation  + $file->score;
        }
    }
}
echo 'SCORE: '.$score.PHP_EOL;
