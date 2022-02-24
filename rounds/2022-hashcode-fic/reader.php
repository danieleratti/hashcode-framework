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
}

class Project
{
    public string $name;
    public int $duration;
    public int $award;
    public int $expire;
    public $roles = [];
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;

list($contributorsCount, $projectsCount) = explode(' ', $content[$fileRow++]);

for ($c = 0; $c < $contributorsCount; $c++) {
    list($contributorName, $skillsCount) = explode(' ', $content[$fileRow++]);
    $contrib = new Contributor();
    $contrib->name = $contributorName;
    for ($s = 0; $s < $skillsCount; $s++) {
        list($skill, $level) = explode(' ', $content[$fileRow++]);
        $contrib->skills[$skill] = $level;
    }
    $contributors[$contributorName] = $contrib;
}

for ($p = 0; $p < $projectsCount; $p++) {
    list($projectName, $daysToComplete, $award, $bestBeforeDays, $rolesCount) = explode(' ', $content[$fileRow++]);
    $project = new Project();
    $project->name = $projectName;
    $project->duration = $daysToComplete;
    $project->award = $award;
    $project->expire = $bestBeforeDays;
    for ($r = 0; $r < $rolesCount; $r++) {
        list($skill, $level) = explode(' ', $content[$fileRow++]);
        $project->roles[] = ["skill" => $skill, "level" => $level];
    }
    $projects[$projectName] = $project;
}

