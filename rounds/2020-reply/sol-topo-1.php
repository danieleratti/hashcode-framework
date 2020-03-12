<?php

use Utils\Cerberus;
use Utils\Log;
use Utils\Stopwatch;

require_once '../../bootstrap.php';

$fileName = 'c';
Cerberus::runClient(['fileName' => $fileName]);

include 'reader.php';

/** @var Developer[] $developers */
/** @var Manager[] $managers */
/** @var array $skills2developers */
/** @var array $company2developers */
/** @var array $company2managers */

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
function occupy(Tile $tile, $p)
{
    /** @var People $p */
    global $remainingDevTiles, $remainingManagerTiles, $remainingDevs, $remainingManagers;

    if(!$p) return;

    $tile->occupy($p);

    if ($p instanceof Developer) {
        $remainingDevs--;
        $remainingDevTiles--;
    } else {
        $remainingManagers--;
        $remainingManagerTiles--;
    }
}

function occupySeed(Tile $tile) { //TODO: migliorare questo!!!!!
    global $developers, $managers;
    if($tile->isDevDesk) {
        occupy($tile, $developers->where('placed', false)->first());
    } else {
        occupy($tile, $managers->where('placed', false)->first());
    }
}

$remainingDevTiles = $tiles->where('isDevDesk', true)->count();
$remainingManagerTiles = $tiles->where('isManagerDesk', true)->count();
$remainingDevs = $developers->count();
$remainingManagers = $managers->count();

//Need a seed
occupySeed($tiles->where('isDevDesk', true)->where('isOccupied', false)->first());
while (($remainingDevTiles > 0 && $remainingDevs > 0) || ($remainingManagerTiles > 0 && $remainingManagers > 0)) {
    /** @var Tile[] $tiles */
    /** @var Tile $tile */
    //Stopwatch::tik('selectTile');
    $tiles = $tiles->where('isDesk', true)->where('isOccupied', false)->sortByDesc('nearsUsedCount');
    if($remainingDevs == 0 || $remainingDevTiles == 0)
        $tiles = $tiles->where('isManagerDesk', true);
    if($remainingManagers == 0 || $remainingManagerTiles == 0)
        $tiles = $tiles->where('isDevDesk', true);
    //Stopwatch::tok('selectTile');
    //Stopwatch::print('selectTile');

    //Stopwatch::tik('algo');
    // Cerco la people migliore

    #$tiles = [$tiles->first()]; #V1!

    foreach($tiles as $tile) {
        $nearDevs = [];
        $nearManagers = [];
        $tests = [];

        foreach ($tile->nears as $near) {
            /** @var Tile $near */
            if ($near && $near->isOccupied) {
                if ($near->isDevDesk) {
                    $nearDevs[] = $near->people;
                } else {
                    $nearManagers[] = $near->people;
                }
            }
        }

        if ($tile->isDevDesk) {
            foreach ($nearDevs as $nearDev) {
                /** @var Developer $nearDev */
                foreach ($scoreDevDev[$nearDev->id] as $testDevId => $score) {
                    /** @var Developer $testDev */
                    if (!$developers[$testDevId]->placed) {
                        $tests[$testDevId] += $score;
                    }
                }
            }
            foreach ($nearManagers as $nearManager) {
                /** @var Manager $nearManager */
                foreach ($scoreManDev[$nearManager->id] as $testDevId => $score) {
                    /** @var Developer $testDev */
                    if (!$developers[$testDevId]->placed) {
                        $tests[$testDevId] += $score;
                    }
                }
            }
        } elseif ($tile->isManagerDesk) {
            foreach ($nearDevs as $nearDev) {
                /** @var Developer $nearDev */
                foreach ($scoreDevMan[$nearDev->id] as $testManId => $score) {
                    /** @var Manager $testManager */
                    if (!$managers[$testManId]->placed) {
                        $tests[$testManId] += $score;
                    }
                }
            }
            foreach ($nearManagers as $nearManager) {
                /** @var Manager $nearManager */
                foreach ($scoreManMan[$nearManager->id] as $testManId => $score) {
                    /** @var Manager $testManager */
                    if (!$managers[$testManId]->placed) {
                        $tests[$testManId] += $score;
                    }
                }
            }
        }

        $bestScore = 0;
        $bestPeopleId = null;
        foreach ($tests as $testPeopleId => $score) {
            if ($score > $bestScore) {
                $bestPeopleId = $testPeopleId;
                $bestScore = $score;
            }
        }

        if ($bestScore == 0) {
            Log::out("Score 0 (search new seed)!!!", 0, 'red'); //Mettere X o cmq qualcosa??? (es. la migliore probabile)
            occupySeed($tile);
        } else {
            if ($tile->isDevDesk) {
                occupy($tile, $developers[$bestPeopleId]);
            } elseif ($tile->isManagerDesk) {
                occupy($tile, $managers[$bestPeopleId]);
            }
        }

        //Stopwatch::tok('algo');
        //Stopwatch::print('algo');
        Log::out("remainingDevTiles=$remainingDevTiles // remainingDevs=$remainingDevs // remainingManagerTiles=$remainingManagerTiles // remainingManagers=$remainingManagers");
    }
}

Log::out('SCORE = ' . getScore());

$fileManager->output(getOutput());
