<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class File
{
    public $id;
    public $compilingTime;
    public $replicationTime;
    /** @var File[]  */
    public $dependencies;

    public function __construct($fileRow1, $fileRow2, $files)
    {
        list($this->id, $this->compilingTime, $this->replicationTime) = explode(' ', $fileRow1);
        $this->dependencies = [];
        foreach (array_slice(explode(' ', $fileRow2), 1) as $id) {
            $this->dependencies[$id] = $files[$id];
        }
    }
}

class TargetFile
{
    /** @var File $file */
    public $file;
    public $deadline;
    public $goalPoints;

    public function __construct($files, $row)
    {
        list($id, $this->deadline, $this->goalPoints) = explode(' ', $row);
        $this->file = $files[$id];
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$fileRows = explode("\n", $fileManager->get());

list($filesCount, $targetCount, $serversCount) = explode(' ', $fileRows[0]);
$filesFileRows = array_slice($fileRows, 1, $filesCount * 2);
$targetsFileRows = array_slice($fileRows, $filesCount * 2 + 1);

/** @var File[] $files */
$files = [];
for ($i = 0; $i < count($filesFileRows); $i += 2) {
    $file = new File($filesFileRows[$i], $filesFileRows[$i + 1], $files);
    $files[$file->id] = $file;
}

/** @var TargetFile $targets */
$targets = [];
foreach ($targetsFileRows as $targetsFileRow) {
    $targets[] = new TargetFile($files, $targetsFileRow);
}

class OutServer
{
    public $id;
    public $freeAt = 0;
    public $filesTime = [];

    public function __construct($id)
    {
        $this->id = $id;
    }
}

class OutServers
{
    /** @var OutServer[] */
    public $servers = [];

    public function __construct()
    {
        global $serversCount;
        for ($i = 0; $i < $serversCount; $i++) {
            $this->servers[$i] = new OutServer($i);
        }
    }

    public function setFile($fileId, $serverId)
    {
        global $files;
        $file = $files[$fileId];
        foreach ($file->dependencies as $depId => $dep) {
            if (array_search($depId, array_keys($this->servers[$serverId]->filesTime)) < 0)
                die("occhio alle dipendenze, scemo");
        }

        $server = $this->servers[$serverId];

        $lastDepTime = max(0, $server->freeAt);
        foreach ($file->dependencies as $dep) {
            if (!$lastDepTime || $lastDepTime < $server->filesTime[$dep->id]) {
                $lastDepTime = $server->filesTime[$dep->id];
            }
        }

        $server->freeAt += $files[$fileId]->compilingTime;
        $compilingTime = $lastDepTime + $files[$fileId]->compilingTime;
        $server->filesTime[$fileId] = min($compilingTime, $server->filesTime[$fileId] ?: PHP_INT_MAX);

        foreach ($this->servers as $server) {
            if ($server->id == $serverId)
                continue;
            $time = $compilingTime + $file->replicationTime;
            $server->filesTime[$fileId] = min($time, $server->filesTime[$fileId] ?: PHP_INT_MAX);
        }

    }
}

function verifyOutput($output)
{
    global $files, $serversCount, $targets;

    $servers = new OutServers();

    $outrows = explode("\n", $output);

    if (trim($outrows[0]) != (count($outrows) - 1))
        die("Non sai neanche contare, scemo");

    foreach (array_slice($outrows, 1) as $outRow) {
        $row = explode(" ", trim($outRow));
        if (count($row) !== 2 || !$files[$row[0]] || $row[1] >= $serversCount)
            die("Hai cannato pure il formato del file, scemo");

        $servers->setFile($row[0], $row[1]);
    }

    $score = 0;

    /** @var TargetFile $target */
    foreach ($targets as $target) {
        $minTargetTime = null;
        foreach ($servers->servers as $server) {
            $fileTime = $server->filesTime[$target->file->id];
            if ($fileTime && (!$minTargetTime || $fileTime < $minTargetTime))
                $minTargetTime = $fileTime;
        }

        if ($minTargetTime && $minTargetTime <= $target->deadline) {
            $score += $target->goalPoints + ($target->deadline - $minTargetTime);
        }
    }

    echo "SCORE: $score";
}
