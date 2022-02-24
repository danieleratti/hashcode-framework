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
    /** @var string[] */
    public array $skills = [];
}

class Project
{
    public string $name;
    /** @var int */
    public int $duration;
    /** @var int */
    public int $award;
    /** @var int */
    public int $expire;
    /** @var array[] */
    public array $roles = [];

    public function canGetSomePointAt($time) {
        return $this->expire - ($time + $this->duration) + $this->award > 0;
    }
}

class Output
{
    private $rows = [];
    private $freeAt = [];
    private $score = null;

    public function setProject(Project $project, array $contributors)
    {

        $this->rows[] = [
            'project' => $project,
            'contributors' => $contributors,
        ];
    }

    public function setProjectAndScore(Project $project, array $contributors)
    {
        $this->setProject($project, $contributors);

        $maxFreeAt = 0;
        foreach ($contributors as $contributor) {
            $contribFreeAt = $this->freeAt[$contributor->name];
            if ($this->freeAt[$contributor->name] > $maxFreeAt)
                $maxFreeAt = $contribFreeAt;
        }

        $projectFinishAt = $maxFreeAt + $project->duration;

        foreach ($contributors as $contributor) {
            $this->freeAt[$contributor->name] = $projectFinishAt;
        }

        $this->score += max(0, $project->award - max(0, $projectFinishAt - $project->expire));
    }

    public function save()
    {
        /** @var FileManager */
        global $fileManager;

        $result = [count($this->rows)];

        foreach ($this->rows as $row) {
            $result[] = $row['project']->name;
            $result[] = implode(' ', array_map(function ($contrib) {
                return $contrib->name;
            }, $row['contributors']));
        }

        $fileManager->outputV2(implode("\n", $result));

        if ($this->score !== null)
            echo "SCORE: " . $this->score;
    }
}

$OUTPUT = new Output();
global $OUTPUT;

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;

list($contributorsCount, $projectsCount) = explode(' ', $content[$fileRow++]);

for ($c = 0;
     $c < $contributorsCount;
     $c++) {
    list($contributorName, $skillsCount) = explode(' ', $content[$fileRow++]);
    $contrib = new Contributor();
    $contrib->name = $contributorName;
    for ($s = 0;
         $s < $skillsCount;
         $s++) {
        list($skill, $level) = explode(' ', $content[$fileRow++]);
        $contrib->skills[$skill] = (int)$level;
    }

    $contributors[$contributorName] = $contrib;
}

for ($p = 0; $p < $projectsCount; $p++) {
    list($projectName, $daysToComplete, $award, $bestBeforeDays, $rolesCount) = explode(' ', $content[$fileRow++]);
    $project = new Project();
    $project->name = $projectName;
    $project->duration = (int)$daysToComplete;
    $project->award = (int)$award;
    $project->expire = (int)$bestBeforeDays;
    for ($r = 0; $r < $rolesCount; $r++) {
        list($skill, $level) = explode(' ', $content[$fileRow++]);
        $project->roles[] = ["skill" => $skill, "level" => (int)$level];
    }
    $projects[$projectName] = $project;
}
