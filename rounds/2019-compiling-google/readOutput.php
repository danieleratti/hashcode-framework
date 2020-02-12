<?php

$fileName = 'a';
$outputName = 'd_soluzione.txt';

include 'reader.php';

$content = trim(file_get_contents(__DIR__ . '/output/' . $outputName));
$rows = explode("\n", $content);
//$rows = explode(" ", $rows);

$score = 0;
array_shift($rows);

foreach ($rows as $el){
    $a = explode(" ", $el);
    $file = $files[$a[0]];
    //$server = $a[1];
    if(in_array($file->filename, array_keys($targetFiles))){
        if($file->timeCompilation <= $file->deadLine){
            $score += $file->deadLine - $file->timeCompilation  + $file->score;
        }
    }
}
echo 'SCORE: '.$score.PHP_EOL;
