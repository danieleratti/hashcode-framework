<?php

include('reader.php');

function getBestServerIndex()
{
    global $servers;
    $bestIndex = 0;
    $minCurrentTime = $servers[0]->currentTime;
    for ($i = 1; $i < count($servers); $i++) {
        if ($servers[$i]->currentTime < $minCurrentTime) {
            $minCurrentTime = $servers[$i]->currentTime;
            $bestIndex = $i;
        }
    }
    return $bestIndex;
}

function writeOutput()
{
    global $fileManager;
    global $compilationHistory;
    $content = "";
    $content .= count($compilationHistory) . "\n";
    for ($i = 0; $i < count($compilationHistory); $i++) {
        $content .= $compilationHistory[$i] . "\n";
    }
    $fileManager->output(trim($content));
}

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

    $a = 0;

    if ($filename == 'c5')
        $a = 1;

    /** @var File $file */
    $file = $files[$filename];
    if ($level > 0 && !in_array(['filename' => $filename, 'level' => $level], $actualDep))
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

$targetFiles = array_slice($files, -$numTargetFiles);
$arr = [];
/** @var File $file */
foreach ($targetFiles as $file) {
    $level = 0;
    $dependencies = [];
    getDependencies($file->filename, $dependencies, $level);
    $keys = array_column($dependencies, 'level');
    array_multisort($keys, SORT_DESC, $dependencies);
    $file->dipendenzeSeb = $dependencies;
}
array_multisort(array_map('dipendenzeSeb', $targetFiles), SORT_ASC, $targetFiles);
$serverManager = new ServerManager($servers);
foreach ($targetFiles as $file) {
    for ($i = 0; $i < count($file->dipendenzeSeb); $i++) {
        $serverManager->addFile($files[$file->dipendenzeSeb[$i]['filename']]);
    }
    $serverManager->addFile($files[$file->filename]);
}

