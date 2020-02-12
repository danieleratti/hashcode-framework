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
