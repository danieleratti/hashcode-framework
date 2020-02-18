<?php

use Utils\FileManager;

$fileName = 'a';

$servers = $files = [];

$fileManager = new FileManager($fileName);

$content = str_replace("\r", "", $fileManager->get());
$content = explode("\n", $content);

list($numCompiledFiles, $numTargetFiles, $numServers) = explode(' ', $content[0]);


$score = 0;
array_shift($arr);
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

