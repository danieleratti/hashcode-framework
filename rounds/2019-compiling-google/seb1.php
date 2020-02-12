<?php

include('reader.php');

/**
 * @param string $filename
 * @param array $actualDep
 * @param int $level
 * @return mixed
 */

//DFS DEEP FIRST SEARCH A MANETTAAAA :)
function getDependencies($filename, &$actualDep, $level)
{
    global $files;

    /** @var File $file */
    $file = $files[$filename];
    if ($level > 0)
        $actualDep[] = ['filename' => $filename, 'level' => $level];
    if (!$file->hasDependencies) {
        return $actualDep;
    }
    $dipendenzeAttuali = $file->dependencies;

    foreach ($dipendenzeAttuali as $fileNameDep) {
        getDependencies($fileNameDep, $actualDep, $level + 1);
    }
    // return $actualDep;
}

$targetCounter = $numTargetFiles;

$targetFiles = array_slice($files, -$targetCounter);
$arr = [];

/** @var File $file */
foreach ($targetFiles as $file) {
    $level = 0;
    $dependencies = [];
    getDependencies($file->filename, $dependencies, $level);

    $keys = array_column($dependencies, 'level');

    array_multisort($keys, SORT_DESC, $dependencies);

    $file->dependencies = $dependencies;
}

array_multisort(array_map('dependencies', $targetFiles), SORT_ASC, $targetFiles);

echo "CIAO";


