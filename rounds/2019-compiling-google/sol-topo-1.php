<?php

$fileName = 'a';

require_once 'reader-topo.php';

/**
 * F U N C T I O N S
 */

/*
 * $filter [c1, c2, ...] <- only the involved files (calculated by the getInvolvedFiles function)
 * $t = time of the earliest server free!
 */
function prepareFiles($filterIdFiles = null)
{
    global $files, $preparedFiles, $preparedFilesIndirect;
    $preparedFiles = [];
    $preparedFilesIndirect = [];

    foreach ($files as $file) {
        if ($filterIdFiles === null || in_array($file->id, $filterIdFiles))
            prepareFile($file);
    }

    foreach ($files as $file) {
        if ($filterIdFiles === null || in_array($file->id, $filterIdFiles))
            prepareFileIndirect($file);
    }
}

function prepareFile($file)
{
    global $files, $preparedFiles;
    if ($preparedFiles[$file->id])
        return;
    $preparedFiles[$file->id] = true;

    //run
    $file->goalPointsOfDependants = 0;

    // align dependencies
    /** @var File $file */
    foreach ($file->dependenciesIds as $depId) {
        $depFile = $files[$depId];
        prepareFile($depFile);

        $depFile->goalPointsOfDependants += $file->goalPoints;
        if (!in_array($file->id, $depFile->dependantsIds))
            $depFile->dependantsIds[] = $file->id;
    }
}

function prepareFileIndirect($file)
{
    global $preparedFilesIndirect;
    if ($preparedFilesIndirect[$file->id])
        return;
    $preparedFilesIndirect[$file->id] = true;

    //run
    $file->indirectDependantsIds = getRecursiveIndirectDependants($file->id);
    $file->indirectDependenciesIds = getRecursiveIndirectDependencies($file->id);
}

function getRecursiveIndirectDependants($fileId)
{
    global $files;

    $ret = [];
    /** @var File $file */
    foreach ($files[$fileId]->dependantsIds as $id) {
        $ret[] = $id;
        foreach (getRecursiveIndirectDependants($id) as $_id) {
            if (!in_array($_id, $ret))
                $ret[] = $_id;
        }
    }
    return $ret;
}

function getRecursiveIndirectDependencies($fileId)
{
    global $files;

    $ret = [];
    /** @var File $file */
    foreach ($files[$fileId]->dependenciesIds as $id) {
        $ret[] = $id;
        foreach (getRecursiveIndirectDependencies($id) as $_id) {
            if (!in_array($_id, $ret))
                $ret[] = $_id;
        }
    }
    return $ret;
}

function whenFileReady($serverId, $fileId)
{
    global $servers;
    /** @var Server $server */
    $server = $servers[$serverId];
    return $server->filesAt[$fileId];
}

function compileFile($serverId, $fileId)
{
    global $SCORE;
    global $servers, $files;
    /** @var Server $server */
    $server = $servers[$serverId];
    $file = $files[$fileId];

    $freeAt = $server->freeAt;
    foreach ($file->indirectDependenciesIds as $depId) {
        $depReadyAt = whenFileReady($serverId, $depId);
        if (!isset($depReadyAt))
            die("FATAL: depReadyAt ($serverId, $depId) not set. Can't compile!");
        $freeAt = max($freeAt, $depReadyAt);
    }

    $freeAt += $file->compilingTime;

    $server->freeAt = $freeAt;
    $server->filesAt[$fileId] = $freeAt;
    /** @var Server $server */
    foreach ($servers as $_server) {
        $_server->filesAt[$fileId] = $_server->filesAt[$fileId] ? min($_server->filesAt[$fileId], $freeAt + $file->replicationTime) : ($freeAt + $file->replicationTime);
    }

    if ($file->isTarget && $freeAt <= $file->deadline) {
        $score = $file->goalPoints + ($file->deadline - $freeAt);
        $SCORE += $score;
    }
}


/**
 * R U N T I M E
 */

$SCORE = 0;

// heating
prepareFiles();


//print_r($files['c1']);
//echo "\n";
//print_r($files['c2']);

compileFile(1, 'c1');
compileFile(0, 'c0');
compileFile(1, 'c3');
compileFile(0, 'c2');
compileFile(1, 'c2');
compileFile(0, 'c4');
compileFile(1, 'c5');
print_r($servers[0]);
echo $SCORE;
