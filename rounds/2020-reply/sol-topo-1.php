<?php

use Utils\Cerberus;
use Utils\Log;
use Utils\Stopwatch;

require_once '../../bootstrap.php';

$fileName = 'b';
Cerberus::runClient(['fileName' => $fileName]);

include 'reader.php';

/** @var Developer[] $developers */
/** @var Manager[] $managers */
/** @var array $skills2developers */
/** @var array $company2developers */
/** @var array $company2managers */

/* functions */
function calcScoreBetweenPeople($p1, $p2) //REMINDER: togliere anche poi dalla lista degli affinity quando si fa occupy
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

/* The Real Algo */
$freeTiles = [];



die("a=$n");

/*
$developers[0]->occupy(1, 1);
$developers[1]->occupy(1, 4);
$developers[4]->occupy(2, 3);
$developers[5]->occupy(2, 4);

$managers[0]->occupy(2, 1);
$managers[2]->occupy(2, 2);
*/

Log::out('SCORE = ' . getScore());

$fileManager->output(getOutput());
