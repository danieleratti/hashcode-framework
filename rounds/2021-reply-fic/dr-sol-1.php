<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

/* CONFIG */
$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);
Autoupload::init();
include __DIR__ . '/dr-reader.php';

/* VARIABLES */
/** @var int $W */
/** @var int $H */
/** @var int $buildingsCount */
/** @var int $antennasCount */
/** @var int $reward */
/** @var Building[] $BUILDINGS */
/** @var Antenna[] $ANTENNAS */

define('bigPixelSize', 100); // TUNE THIS

$SCORE = 0;
$bigPixel2antennas = [];
$bigPixel2buildings = [];
$reachedBuildings = [];
$unreachedBuildings = [];

/* FUNCTIONS */
/**
 * @param $semaphores
 * @return string
 */
function getOutput($semaphores)
{
    $output = [];
    $output[] = count($semaphores);
    $output = implode("\n", $output);
    return $output;
}

function dist($r1, $c1, $r2, $c2)
{
    return abs($r1 - $r2) + abs($c1 - $c2);
}

function getBigPixel($r, $c)
{
    return [floor($r / bigPixelSize), floor($c / bigPixelSize)];
}

function getNearBuildings($r, $c, $range)
{
    global $bigPixel2buildings;

    $bigPixelNeighbors = ceil($range / bigPixelSize);
    $bigPixel = getBigPixel($r, $c);
    $r = $bigPixel[0];
    $c = $bigPixel[1];

    $buildings = [];
    for ($_r = $r - $bigPixelNeighbors; $_r <= $r + $bigPixelNeighbors; $_r++) {
        for ($_c = $c - $bigPixelNeighbors; $_c <= $c + $bigPixelNeighbors; $_c++) {
            if ($bigPixel2buildings[$_r][$_c]) {
                foreach ($bigPixel2buildings[$_r][$_c] as $building) {
                    $dist = dist($r, $c, $building->r, $building->c);
                    if ($dist <= $range)
                        $buildings[] = ['building' => $building, 'distance' => $dist];
                }
                //$buildings = array_merge($buildings, $bigPixel2buildings[$_r][$_c]);
            }
        }
    }

    return $buildings;
}

function getNearAntennas($r, $c, $range)
{
    global $bigPixel2antennas;

    $bigPixelNeighbors = ceil($range / bigPixelSize);
    $bigPixel = getBigPixel($r, $c);
    $r = $bigPixel[0];
    $c = $bigPixel[1];

    $antennas = [];
    for ($_r = $r - $bigPixelNeighbors; $_r <= $r + $bigPixelNeighbors; $_r++) {
        for ($_c = $c - $bigPixelNeighbors; $_c <= $c + $bigPixelNeighbors; $_c++) {
            if ($bigPixel2antennas[$_r][$_c]) {
                foreach ($bigPixel2antennas[$_r][$_c] as $antenna) {
                    $dist = dist($r, $c, $antenna->r, $antenna->c);
                    if ($dist <= $range)
                        $antennas[] = ['antenna' => $antenna, 'distance' => $dist];
                }
            }
        }
    }

    return $antennas;
}

/**
 * @param Antenna $antenna
 * @param $r
 * @param $c
 */
function placeAntenna($antenna, $r, $c)
{
    global $bigPixel2antennas, $SCORE;

    // bigPixel
    $antenna->placed = true;
    $antenna->r = $r;
    $antenna->c = $c;
    $bigPixel = getBigPixel($antenna->r, $antenna->c);
    $bigPixel2antennas[$bigPixel[0]][$bigPixel[1]][$antenna->id] = $antenna;

    // scores
    $buildings = getNearBuildings($antenna->r, $antenna->c, $antenna->range);
    foreach ($buildings as $building) {
        $score = calcScore($antenna, $building);
        if ($score > $building->score) {
            $deltaScore = $score - $building->score;
            $building->score = $score;
            $SCORE += $deltaScore;
        }
    }
}

/**
 * @param Antenna $antenna
 * @param Building $building
 */
function calcScore($antenna, $building)
{
    $dist = dist($antenna->r, $antenna->c, $building->r, $building->c);
    if ($dist > $antenna->range) {
        Log::error("dist $dist > antenna->range " . $antenna->range);
    }
    $score = $building->speed * $antenna->speed - $building->latency * $dist; // se negativo???
    return $score;
}

/* ALGO */
Log::out("Heating bigPixels...");
foreach ($BUILDINGS as $building) {
    $bigPixel = getBigPixel($building->r, $building->c);
    $bigPixel2buildings[$bigPixel[0]][$bigPixel[1]][$building->id] = $building;
}

/* REAL ALGO */
Log::out("Real algo...");

$buildings = getNearBuildings(0, 0, 7);
die();

// megacells


/* SCORING & OUTPUT */
Log::out("SCORE($fileName) = ");
//$fileManager->outputV2(getOutput([]), 'score_' . $SCORE);
