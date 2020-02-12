<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class File
{
    public $id;
    public $compilingTime;
    public $replicationTime;
    public $dependenciesIds;

    public $isTarget = false;
    public $deadline = null;
    public $goalPoints = 0;

    public $dependantsIds;

    public $indirectDependantsIds;
    public $indirectDependenciesIds;

    public $replicatedAt = null; //time replicated everywhere
    public $readyAt = []; //<serverId> : <time>

    public $nRemainingDependencies;
    public $goalPointsOfDependants;
    public $goalPointsOfIndirectDependants;

    public $nearestStartDeadlineNotExpired;

    public function __construct($fileRow1, $fileRow2)
    {
        list($this->id, $this->compilingTime, $this->replicationTime) = explode(' ', $fileRow1);
        $this->dependenciesIds = array_slice(explode(' ', $fileRow2), 1);
    }

    public function enrichAsTarget($deadline, $goalPoints)
    {
        $this->isTarget = true;
        $this->deadline = $deadline;
        $this->goalPoints = $goalPoints;
    }
}

class Server
{
    public $id;
    public $freeAt;
    public $filesAt;

    public function __construct($id)
    {
        $this->id = $id;
        $this->freeAt = 0;
        $this->filesAt = []; //fileId : timeFreeAt
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
    $file = new File($filesFileRows[$i], $filesFileRows[$i + 1]);
    $files[$file->id] = $file;
}

$targets = [];
foreach ($targetsFileRows as $targetsFileRow) {
    list($id, $deadline, $goalPoints) = explode(' ', $targetsFileRow);
    $files[$id]->enrichAsTarget($deadline, $goalPoints);
}

$servers = [];
for ($i = 0; $i < $serversCount; $i++)
    $servers[$i] = new Server($i);
