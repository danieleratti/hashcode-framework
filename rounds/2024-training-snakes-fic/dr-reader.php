<?php

global $fileName;

/** @var Contributor[] */
global $contributors;

/** @var Project[] */
global $projects;

use Utils\FileManager;

require_once '../../bootstrap.php';

class Contributor
{
    public string $name;
    public $skills = [];
    public $freeAt = 0; //topo
    public $skillImproved = null; //topo
}

class Project
{
    public string $name;
    public int $duration;
    public int $award;
    public int $expire;
    public $roles = [];
    public $score = 0;
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;


print_r($content);
