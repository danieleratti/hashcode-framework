<?php
//Reader
use Utils\FileManager;

require_once '../../bootstrap.php';

class File
{
    // tempo replicazione
    public $timeReplication = 0;
    public $timeCompilation = 0;
    public $filename = '';
    public $deadLine = 0;
    public $score = 0;
    public $dependencies = [];
    public $hasDependencies = false;
    public $alreadyCompiled = false;
    public $alreadyReplicated = false;

    public function __construct($filename, $timeCompilation, $timeReplication, $dependencies = null)
    {
        $this->filename = $filename;
        $this->timeCompilation = $timeCompilation;
        $this->timeReplication = $timeReplication;
        if ($dependencies) {
            $this->hasDependencies = true;
            $this->dependencies = $dependencies;
        }
    }
}

class Server
{

}

$fileName = 'a';

$fileManager = new FileManager($fileName);

$content = str_replace("\r", "", $fileManager->get());
$content = explode("\n", $content);

list($numCompiledFiles, $numTargetFiles, $numServers) = explode(' ', $content[0]);

$numCompiledFiles = (int)$numCompiledFiles;
$numTargetFiles = (int)$numTargetFiles;
$numServers = (int)$numServers;
array_shift($content);

$files = [];
for ($i = 0; $i < $numCompiledFiles; $i++) {
    list($fileName, $compilationTime, $replicationTime) = explode(' ', $content[$i * 2]);
    $deps = explode(' ', $content[($i * 2) + 1]);
    $dependencies = [];
    for ($j = 0; $j < $deps[0]; $j++) {
        $dependencies[] = $deps[$j + 1];
    }
    $files[$fileName] = new File($fileName, $compilationTime, $replicationTime, $dependencies);
}

$startingT = $i;
for ($i = $startingT; $i < $numTargetFiles; $i++) {
    list($fileName, $deadline, $score) = explode(' ', $content[$i]);
    $files[$fileName]->deadline = $deadline;
    $files[$fileName]->score = $score;
}




