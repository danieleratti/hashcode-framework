<?php

use Utils\Cerberus;
use Utils\Stopwatch;

require_once '../../bootstrap.php';

$fileName = 'f';
Cerberus::runClient(['fileName' => $fileName]);

/** @var \Utils\Collection $managers */
/** @var \Utils\Collection $developers */
/** @var \Utils\Collection $tiles */
/** @var Tile[] $tiles */
/** @var int $numDevelopers */
/** @var int $numManagers */
/** @var int $WIDTH */
/** @var int $HEIGHT */
/** @var Developer[] $developers */
/** @var Manager[] $managers */
/** @var array $skills2developers */
/** @var array $company2developers */
/** @var array $company2managers */

include 'reader.php';

/* TOPO */

/* functions */
function calcScoreBetweenPeople($p1, $p2)
{
    global $scoreDevDev, $scoreDevMan, $scoreManDev, $scoreManMan;
    if ($p1 instanceof Developer) {
        if ($p2 instanceof Developer) {
            if (!isset($scoreDevDev[$p1->id][$p2->id])) {
                $score = getPairScore($p1, $p2);
                $scoreDevDev[$p1->id][$p2->id] = $score;
                $scoreDevDev[$p2->id][$p1->id] = $score;
            }
        } else {
            if (!isset($scoreDevMan[$p1->id][$p2->id])) {
                $score = getPairScore($p1, $p2);
                $scoreDevMan[$p1->id][$p2->id] = $score;
                $scoreManDev[$p2->id][$p1->id] = $score;
            }
        }
    }
    if ($p1 instanceof Manager) {
        if (!isset($scoreManMan[$p1->id][$p2->id])) {
            $score = getPairScore($p1, $p2);
            $scoreManMan[$p1->id][$p2->id] = $score;
            $scoreManMan[$p2->id][$p1->id] = $score;
        }
    }
}

/* DEBUG */

/* calculate the score between dev-managers */
//REMINDER TODO: togliere anche poi dalla lista degli scores quando si fa occupy
$scoreDevDev = [];
$scoreDevMan = [];
$scoreManDev = [];
$scoreManMan = [];

Stopwatch::tik('calcAffinity');
foreach ($developers as $d1) {
    foreach ($d1->skills as $skill) {
        foreach ($skills2developers[$skill] as $d2) {
            calcScoreBetweenPeople($d1, $d2);
        }
    }
    foreach ($company2developers[$d1->company] as $d2) {
        calcScoreBetweenPeople($d1, $d2);
    }
    foreach ($company2managers[$d1->company] as $m2) {
        calcScoreBetweenPeople($d1, $m2);
    }
}

foreach ($managers as $m1) {
    foreach ($company2managers[$m1->company] as $m2) {
        calcScoreBetweenPeople($m1, $m2);
    }
}
Stopwatch::tok('calcAffinity');
Stopwatch::print();

/* FINE TOPO */

function getBestDevCouple()
{
    global $scoreDevDev;
    $bestScore = -1;
    $bestCouple = null;
    foreach ($scoreDevDev as $d1 => $temp) {
        foreach ($temp as $d2 => $score) {
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCouple = [$d1, $d2];
            }
        }
    }
    return $bestCouple;
}

function getBestNeighbor($id1, $type1, $type2)
{
    global $scoreDevDev, $scoreDevMan, $scoreManDev, $scoreManMan;
    $bestScore = -1;
    $bestId = null;
    $array = $type1 == 'dev' ? ($type2 == 'dev' ? $scoreDevDev : $scoreDevMan) : ($type2 == 'dev' ? $scoreManDev : $scoreManMan);
    foreach ($array[$id1] as $id2 => $score) {
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = $id2;
        }
    }
    return $bestId;
}

$totalScore = 0;

/* Create groups */
include_once 'groups.inc.php';
/** @var \Utils\Collection $groups */

// Do things
$groups = $groups->sortByDesc('devCount');

function populateGroup(Tile $startTile, $startId)
{
    global $scoreDevDev, $scoreDevMan, $scoreManDev, $scoreManMan;
    global $developers, $managers;
    $startType = $startTile->isDevDesk ? 'dev' : 'manager';
    $entities = $startTile->isDevDesk ? $developers : $managers;
    $person = $entities->get($startId);
    if (!$person) {
        echo "Errore";
        return;
    }
    $startTile->occupy($person);
    if ($startTile->isDevDesk) {
        unset($scoreDevDev[$startId]);
        unset($scoreDevMan[$startId]);
        foreach ($scoreDevDev as $sa => $v)
            unset ($scoreDevDev[$sa][$startId]);
        foreach ($scoreManDev as $sa => $v)
            unset ($scoreManDev[$sa][$startId]);
    } else {
        unset($scoreManDev[$startId]);
        unset($scoreManMan[$startId]);
        foreach ($scoreManMan as $sa => $v)
            unset ($scoreManMan[$sa][$startId]);
        foreach ($scoreDevMan as $sa => $v)
            unset ($scoreDevMan[$sa][$startId]);
    }
    echo "Occupy [{$startTile->r}][{$startTile->c}] with $startType #$startId\n";
    foreach ($startTile->nears as $n) {
        if ($n->isOccupied) continue;
        $currentId = getBestNeighbor($startId, $startType, $n->isDevDesk ? 'dev' : 'manager');
        if ($currentId === null) continue;
        populateGroup($n, $currentId);
    }
}

foreach ($groups as $g) {
    /** @var Group $g */
    [$id1, $id2] = getBestDevCouple();
    if ($id1 !== null) {
        populateGroup($g->tiles[0], $id1);
    }
}


echo "Created " . count($groups) . " groups\n\n";


$fileManager->output(getOutput());
