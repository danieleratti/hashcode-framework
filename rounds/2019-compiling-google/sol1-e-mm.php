<?php

$fileName = 'e';

/** @var TargetFile[] $targets */
/** @var File[] $files */

require_once(__DIR__ . '/reader.php');

class Job
{
    /** @var int $startedAt */
    public $startedAt = null;
    /** @var int $completedAt */
    public $completedAt = null;
    /** @var File $file */
    public $file;

    /**
     * Job constructor.
     * @param File $file
     * @param int $startedAt
     * @param int $completedAt
     */
    public function __construct($file, $startedAt, $completedAt)
    {
        $this->file = $file;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
    }
}

class Server
{
    /** @var int $id */
    public $id;
    /** @var Job[] $jobs */
    public $jobs = [];
    /** @var int $freeAt */
    public $freeAt = 0;

    /**
     * Server constructor.
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param Job $job
     */
    public function assign($job)
    {
        $this->jobs[] = $job;
        $this->freeAt = $job->completedAt;
    }
}

class ServerManager
{
    /** @var Server[] $servers */
    public $servers = [];

    /**
     * ServerManager constructor.
     * @param int $count
     */
    public function __construct($count)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->servers[] = new Server($i);
        }
    }

    public function getFirstFreeServer()
    {
        $minFreeAt = PHP_INT_MAX;
        $bestServer = null;
        foreach ($this->servers as $server) {
            if ($server->freeAt < $minFreeAt) {
                $minFreeAt = $server->freeAt;
                $bestServer = $server;
            }
        }
        return $bestServer;
    }

    /**
     * @param File $file
     */
    public function assignFileToFirstFree($file)
    {
        $server = $this->getFirstFreeServer();
        $willFinishAt = $server->freeAt + $file->compilingTime;
        $server->assign(new Job($file, $server->freeAt, $server->freeAt + $file->compilingTime));
        echo "Assign file {$file->id} to server {$server->id}, will finish at {$willFinishAt}\n";
    }
}

$serverManager = new ServerManager($serversCount);

$selectedTargets = ['clt'];
foreach ($targets as $target) {
    if (!in_array($target->file->id, $selectedTargets)) {
        continue;
    }
    echo "\n\nFor target {$target->file->id}\n\n";
    $dependencies = $target->file->dependencies;
    uasort($dependencies, function ($d1, $d2) {
        /** @var File $d1 */
        /** @var File $d2 */
        return $d1->compilingTime < $d2->compilingTime;
    });
    foreach ($dependencies as $file) {
        $serverManager->assignFileToFirstFree($file);
    }
}
