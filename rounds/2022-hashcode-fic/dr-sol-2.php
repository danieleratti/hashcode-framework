<?php

use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Contributor[] */
global $contributors;
/** @var Project[] */
global $projects;

/* Config & Pre runtime */
/**
 * b => 37740
c => 133826
d => 15497
e => 50979
f => 54614
 */
$fileName = 'f';
$lastUploadedScore = 88196;
$param1 = 1;

Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);
Autoupload::init();

/* Reader */
include_once 'dr-reader.php';

/* Vars */
$SCORE = 0;
$T = 0;
/**
 * skillMatrixMinLevel[ PHP ] = [ 10 (at least that level) ][ $contributor->name ] = $contributor; (N)
 * skillMatrixExactLevel[ PHP ] = [ 10 (exactly that level) ][ $contributor->name ] = $contributor; (N)
 */
$skillMatrixMinLevel = [];
$skillMatrixExactLevel = [];
$freeContributors = [];
$busyContributors = [0 => $contributors]; // [timeOfRelease] = [contributors...];
$remainingProjects = $projects;
$projectsDone = [];

/* Functions */
function doProject(Project $project, array $contributors)
{
    global $fileName, $SCORE, $T, $remainingProjects, $projectsDone;
    $freeAt = $T + $project->duration;
    $score = $project->award;

    $bestSkillsLevel = [];
    foreach ($contributors as $contributor) {
        foreach ($contributor->skills as $skillName => $skillLevel) {
            $bestSkillsLevel[$skillName] = max($bestSkillsLevel[$skillName], $skillLevel);
        }
    }

    foreach ($project->roles as $roleNumber => $role) {
        $skillName = $role["skill"];
        $skillLevel = $role["level"];
        $skillImproved = null;
        if ($bestSkillsLevel[$skillName] < $skillLevel)
            Log::error("Assert 1 " . $bestSkillsLevel[$skillName] . " < " . $skillLevel . " for skill $skillName");
        $contributor = $contributors[$roleNumber];
        /** @var Contributor $contributor */
        if ($contributor->skills[$skillName] < $skillLevel - 1)
            Log::error("Assert 2 " . $contributor->skills[$skillName] . " < " . ($skillLevel - 1) . " for skill $skillName and contributor " . $contributor->name);
        if ($contributor->skills[$skillName] == $skillLevel - 1 || $contributor->skills[$skillName] == $skillLevel)
            $skillImproved = $skillName;
        occupyContributor($contributor, $freeAt, $skillImproved);
    }

    if ($freeAt > $project->expire)
        $score = max(0, $score - ($freeAt - $project->expire));
    $SCORE += $score;
    unset($remainingProjects[$project->name]);
    $projectsDone[] = [
        'project' => $project,
        'contributors' => $contributors,
    ];
    Log::out("$fileName) Project " . $project->name . " done. New SCORE = " . $SCORE);
}

function occupyContributor(Contributor $contributor, $freeAt, $skillImproved = null)
{
    global $skillMatrixMinLevel, $skillMatrixExactLevel, $freeContributors, $busyContributors;
    $contributor->freeAt = $freeAt;
    $contributor->skillImproved = $skillImproved;
    unset($freeContributors[$contributor->name]);
    $busyContributors[$freeAt][$contributor->name] = $contributor;
    // fix skill matrix
    foreach ($contributor->skills as $skillName => $skillLevel) {
        unset($skillMatrixExactLevel[$skillName][$skillLevel][$contributor->name]);
        for ($l = 1; $l <= $skillLevel; $l++) {
            unset($skillMatrixMinLevel[$skillName][$l][$contributor->name]);
        }
    }
}

function releaseContributors()
{
    global $T, $busyContributors, $freeContributors, $skillMatrixExactLevel, $skillMatrixMinLevel;
    if (@$busyContributors[$T]) {
        foreach ($busyContributors[$T] as $k => $contributor) {
            /** @var Contributor $contributor */
            $freeContributors[$contributor->name] = $contributor;
            $contributor->freeAt = 0;
            if ($contributor->skillImproved) {
                @$contributor->skills[$contributor->skillImproved]++;
                $contributor->skillImproved = null;
            }
            // fix skillmatrix
            foreach ($contributor->skills as $skillName => $skillLevel) {
                $skillMatrixExactLevel[$skillName][$skillLevel][$contributor->name] = $contributor;
                for ($l = 1; $l <= $skillLevel; $l++) {
                    //if ($l > 1 && !@$skillMatrixMinLevel[$skillName][$l])
                    //    $skillMatrixMinLevel[$skillName][$l] = $skillMatrixMinLevel[$skillName][$l - 1];
                    $skillMatrixMinLevel[$skillName][$l][$contributor->name] = $contributor;
                }
            }
        }
        unset($busyContributors[$T]);
    }
}

function getOutput()
{
    global $projectsDone;
    $output = [];
    $output[] = count($projectsDone);
    foreach ($projectsDone as $p) {
        $output[] = $p['project']->name;
        $cs = [];
        foreach ($p['contributors'] as $contributor) {
            $cs[] = $contributor->name;
        }
        $output[] = implode(" ", $cs);
    }
    return implode("\n", $output);
}

function recalculateRemainingProjectsScores()
{
    global $remainingProjects, $T;

    foreach ($remainingProjects as $project) {
        /** @var Project $project */
        $freeAt = $T + $project->duration;
        $score = $project->award; //GOOD
        if ($freeAt > $project->expire)
            $score = max(0, $score - ($freeAt - $project->expire));

        $advanceDays = max(0, $project->expire - $freeAt); //BAD

        $myscore = $score / ((1 + $advanceDays) * count($project->roles)); //TODO: includere rarità nel reperire le skills! // per ora -1 se non fattibile

        $project->score = $myscore;
    }
}

function getProjectFeasibility(Project $project)
{
    global $skillMatrixMinLevel, $skillMatrixExactLevel;
    $contributorsOutput = [];
    $contributorsTaken = [];
    foreach ($project->roles as $role) {
        $skillName = $role['skill'];
        $skillLevel = $role['level'];
        $bestContributorTakenSkillLevel = null; //TODO: ottimizzare per mentor
        $bestContributorToTake = null;
        if (@$skillMatrixMinLevel[$skillName][$skillLevel] && count($skillMatrixMinLevel[$skillName][$skillLevel]) > 0) {
            foreach ($skillMatrixMinLevel[$skillName][$skillLevel] as $c) {
                /** @var Contributor $c */
                if (!@$contributorsTaken[$c->name] && ($bestContributorTakenSkillLevel == null || $bestContributorTakenSkillLevel > $c->skills[$skillName])) {
                    $bestContributorTakenSkillLevel = $c->skills[$skillName];
                    $bestContributorToTake = $c;
                    if ($bestContributorTakenSkillLevel == $skillLevel)
                        break;
                }
            }
        }
        if (!$bestContributorToTake)
            return null;
        $contributorsTaken[$bestContributorToTake->name] = $bestContributorToTake;
        $contributorsOutput[] = $bestContributorToTake;
    }
    return $contributorsOutput;
}


function getProjectFeasibilityAdvanced(Project $project, $currentContributors)
{
    global $skillMatrixMinLevel, $skillMatrixExactLevel;
    $contributorsPossibles = [];
    $contributorsPossiblesMinusOne = [];
    $contributorsOutput = [];
    $skillsNeeded = [];
    $possibleMentors = []; //[mainSkill][altraSkill][] = $c
    $skill2roleId = [];
    $rolesTouched = [];
    $contributorsTaken = [];

    foreach($currentContributors as $c) {
        $contributorsTaken[$c->name] = $c;
    }

    foreach ($project->roles as $roleId => $role) {
        $skillName = $role['skill'];
        $skillLevel = $role['level'];
        $skillsNeeded[$skillName] = max($skillsNeeded[$skillName], $skillLevel);
        $skill2roleId[$skillName] = $roleId;
    }

    foreach ($project->roles as $roleId => $role) {
        $skillName = $role['skill'];
        $skillLevel = $role['level'];
        $bestContributorTakenSkillLevel = null;
        $bestContributorToTake = null;
        if (@$skillMatrixMinLevel[$skillName][$skillLevel] && count($skillMatrixMinLevel[$skillName][$skillLevel]) > 0) {
            $contributorsPossibles[$roleId] = $skillMatrixMinLevel[$skillName][$skillLevel];
            $contributorsPossiblesMinusOne[$roleId] = $skillMatrixExactLevel[$skillName][$skillLevel-1];
        }
    }

    foreach($contributorsPossibles as $roleId => $cs) {
        foreach($cs as $c) {
            /** @var Contributor $c */
            foreach ($c->skills as $skill => $level) {
                if ($skill != $project->roles[$roleId]['skill']) {
                    if ($skillsNeeded[$skill] && $skillsNeeded[$skill] <= $level) {
                        //$possibleMentors[$project->roles[$roleId]['skill']][$skill][$c->name] = $c;
                        $possibleMentors[$roleId][$skill2roleId[$skill]][$c->name] = $c;
                    }
                }
            }
        }
    }

    foreach($possibleMentors as $roleId => $rolesAlt) {
        foreach($rolesAlt as $roleAlt => $pm) {
            foreach($pm as $mentor) {
                if(!@$rolesTouched[$roleId] && !@$rolesTouched[$roleAlt] && !@$contributorsTaken[$mentor->name]) {
                    if(isset($contributorsPossiblesMinusOne[$roleAlt]) && count($contributorsPossiblesMinusOne[$roleAlt]) > 0) {
                        shuffle($contributorsPossiblesMinusOne[$roleAlt]);
                        $key = array_key_first($contributorsPossiblesMinusOne[$roleAlt]);
                        $randomAlunno = $contributorsPossiblesMinusOne[$roleAlt][$key];
                        $contributorsTaken[$mentor->name] = $mentor;
                        $contributorsTaken[$randomAlunno->name] = $randomAlunno;
                        $currentContributors[$roleId] = $mentor;
                        $currentContributors[$roleAlt] = $randomAlunno;
                        $rolesTouched[$roleId] = true;
                        $rolesTouched[$roleAlt] = true;
                    }
                }
            }
        }
    }

    return $currentContributors;
}


/* Runtime */
releaseContributors();
recalculateRemainingProjectsScores();

/*occupyContributor($freeContributors["Maria"], 1, "Python");
$T = 1;
releaseContributors();*/
/*
doProject($remainingProjects["WebServer"], [$freeContributors["Bob"], $freeContributors["Anna"]]);
$T = 7;
releaseContributors();
doProject($remainingProjects["Logging"], [$freeContributors["Anna"]]);
doProject($remainingProjects["WebChat"], [$freeContributors["Maria"], $freeContributors["Bob"]]);
$T = 12;
releaseContributors();
$T = 17;
releaseContributors();
$output = getOutput();
*/

$lastUploadedScoreDate = 0;

while (true) {
    $preScore = $SCORE;
    if($T <= 10 || $T%100==0)
        Log::out("$fileName) T = $T // SCORE = $SCORE");

    ArrayUtils::array_keysort_objects($remainingProjects, 'score', SORT_DESC);
    foreach ($remainingProjects as $remainingProject) {
        /** @var Project $remainingProject */
        $feasibleContributors = getProjectFeasibility($remainingProject);
        if($feasibleContributors) {
            $feasibleContributors = getProjectFeasibilityAdvanced($remainingProject, $feasibleContributors);
            doProject($remainingProject, $feasibleContributors);
        }
    }

    if($SCORE > $preScore) {
        $fileManager->outputV2(getOutput());
    }

    $T++;
    releaseContributors();
    recalculateRemainingProjectsScores(); // TODO: pesa un sacco così ogni volta...

    if($SCORE > $lastUploadedScore && time()-$lastUploadedScoreDate > 10) {
        $lastUploadedScoreDate = time();
        $lastUploadedScore = $SCORE;
        Log::out("Uploading!", 0, "green");
        Autoupload::submission($fileName, null, getOutput());
    } elseif(time()-$lastUploadedScoreDate > 60) {
        if(count($remainingProjects) == 0)
            die();
    }
}

//Log::out("Uploading!", 0, "green");
//Autoupload::submission($fileName, null, $output);
