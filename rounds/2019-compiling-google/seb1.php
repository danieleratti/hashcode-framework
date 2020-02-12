<?php

include('reader.php');

/**
 * @param string $filename
 * @param array $actualDep
 * @return mixed
 */

//DFS DEEP FIRST SEARCH A MANETTAAAA :)
function getDependencies(string $filename, &$actualDep, $level)
{
    global $files;

    /** @var File $file */
    $file = $files[$filename];

    $actualDep[] = ['filename' => $filename, 'level' => $level];
    if (!$file->hasDependencies) {
        return $actualDep;
    }
    $dipendenzeAttuali = $file->dependencies;

    foreach ($dipendenzeAttuali as $fileNameDep) {
        getDependencies($fileNameDep, $actualDep, $level+1);
    }

    // return $actualDep;
}

$targetCounter = $numTargetFiles;

$targetFiles = array_slice($files, -$targetCounter);
$arr = [];
$level = 0;

$array = getDependencies('c5', $arr, $level);

