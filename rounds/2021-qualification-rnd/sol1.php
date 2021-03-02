<?php

use Utils\FileManager;
use Utils\Log;

$fileName = 'a';

include 'reader.php';

/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Developer[] $DEVELOPERS */
/** @var FileManager $fileManager */
/** @var Map $MAP */

Log::out('Output...');
foreach ($DEVELOPERS as $k=>$dev){
    $y= $dev->posH;
    $x= $dev->posW;
    if($y && $x)
        $output .= $x .' '.$y. PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
foreach ($PROJECTMANAGERS as $pm){
    $y= $pm->posH;
    $x= $pm->posW;
    if($y && $x)
        $output .= $x .' '.$y. PHP_EOL;
    else
        $output .= 'X' . PHP_EOL;
}
$fileManager->outputV2($output, time());

$test = $MAP->getFreeNeighbours(5,0,'#');

die();
