<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Contributor[] */
global $contributors;
/** @var Project[] */
global $projects;
/** @var int $contributorsCount */
global $contributorsCount;
/** @var int $projectsCount */
global $projectsCount;

$fileName = 'a';

/* Reader */
include_once 'reader.php';

$analyzer = new Analyzer($fileName, [
    'contributors_count' => $contributorsCount,
    'projects_count' => $projectsCount,
]);

$analyzer->addDataset('contributors', $contributors, ['skills']);

$analyzer->addDataset('projects', $projects, ['duration', 'award', 'expire', 'roles']);

$analyzer->analyze();

// Images

$skillsLevels = [];
$maxLevel = 0;
$skill2Id = [];
$nextSkillId = 0;
$maxCount = 0;

foreach ($contributors as $c) {
    /** @var Contributor $c */
    foreach ($c->skills as $skill => $level) {
        if(isset($skill2Id[$skill])) {
            $skillId = $skill2Id[$skill];
        } else {
            $skillId = $nextSkillId++;
            $skill2Id[$skill] = $skillId;
        }
        $skillsLevels[$skillId][$level]++;
        if($skillsLevels[$skillId][$level] > $maxCount) $maxCount = $skillsLevels[$skillId][$level];
        if($level > $maxLevel) $maxLevel = $level;
    }
}

$visual = new VisualGradient(count($skillsLevels), $maxLevel + 1);
for ($i = 0; $i < count($skill2Id); $i++) {
    for ($k = 0; $k <= $maxLevel; $k++) {
        $visual->setPixel($i, $k, ($skillsLevels[$i][$k] ?? 0)/$maxCount);
    }
}

$visual->save($fileName."_skill_levels");


$roleLevels = [];
$roleMaxCount = 0;
$roleMaxLevel = 0;
foreach ($projects as $p) {
    /** @var Project $p */
    foreach ($p->roles as $roleId => $roleInfo) {
        $skill = $roleInfo['skill'];
        $level = $roleInfo['level'];
        $skillId = $skill2Id[$skill];
        $roleLevels[$skillId][$level] += $p->award/count($p->roles)/$p->duration;
        if($roleLevels[$skillId][$level] > $roleMaxCount) $roleMaxCount = $roleLevels[$skillId][$level];
        if($level > $roleMaxLevel) $roleMaxLevel = $level;
    }
}

$visual = new VisualGradient(count($roleLevels), $roleMaxLevel + 1);
for ($i = 0; $i < count($skill2Id); $i++) {
    for ($k = 0; $k <= $roleMaxLevel; $k++) {
        $visual->setPixel($i, $k, ($roleLevels[$i][$k] ?? 0)/$roleMaxCount);
    }
}

$visual->save($fileName."_role_levels_x_score_x_duration");

die();
