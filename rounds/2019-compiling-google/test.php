<?php
use Utils\FileManager;
require_once '../../bootstrap.php';

$inputName = 'input06';

$fileManager = new FileManager($inputName);

$content = str_replace("\r", "", $fileManager->get());
$content = explode("\n", $content);

$scores = explode(' ', $content[1]);
$alice = explode(' ', $content[3]);

$newScores = array_values(array_unique($scores));
//echo count($scores).' - '.count($newScores);


ini_set('xdebug.max_nesting_level', 9999);


$a = check($alice, $newScores);
echo 'finished';
writeOutput($a);
//print_r($a);

function check($alice, $newScores, &$arr = []){
    if(count($alice) == 0)
        return $arr;

    foreach($newScores as $key => $v){
        if($alice[0] >= $v){
            $arr[] = $key+1;
            array_shift($alice);
            $newScores = array_slice($newScores, 0, $key, true);
            return check($alice, $newScores, $arr);
        }
    }
    $arr[] = count($newScores)+1 - count($arr);
    array_shift($alice);
    check($alice, $newScores, $arr);

    return $arr;
}

function writeOutput($arr)
{
    echo 'writing';
    global $fileManager;
    $content = "";
    for ($i = 0; $i < count($arr); $i++) {
        $content .= $arr[$i] . "\n";
    }
    echo '--2';
    $fileManager->output(trim($content));
}
