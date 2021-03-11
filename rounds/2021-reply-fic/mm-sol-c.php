<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

/* CONFIG */
$fileName = 'f';
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
/** @var FileManager $fileManager */

define('bigPixelSize', 100); // TUNE THIS

$SCORE = 0;
$rewardGiven = false;
$bigPixel2antennas = [];
$bigPixel2buildings = [];
$reachedBuildings = [];
$unreachedBuildings = [];
$placedAntennas = [];
$remainingAntennas = [];

/* FUNCTIONS */
/**
 * @return string
 */
function getOutput()
{
    global $placedAntennas;
    $output = [];
    $output[] = count($placedAntennas);
    foreach ($placedAntennas as $antenna) {
        $output[] = $antenna->id . " " . $antenna->c . " " . $antenna->r;
    }
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

function getNearBuildings($realR, $realC, $range)
{
    global $bigPixel2buildings;

    $bigPixelNeighbors = ceil($range / bigPixelSize);
    $bigPixel = getBigPixel($realR, $realC);
    $r = $bigPixel[0];
    $c = $bigPixel[1];

    $buildings = [];
    for ($_r = $r - $bigPixelNeighbors; $_r <= $r + $bigPixelNeighbors; $_r++) {
        for ($_c = $c - $bigPixelNeighbors; $_c <= $c + $bigPixelNeighbors; $_c++) {
            if ($bigPixel2buildings[$_r][$_c]) {
                foreach ($bigPixel2buildings[$_r][$_c] as $building) {
                    $dist = dist($realR, $realC, $building->r, $building->c);
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
    global $bigPixel2antennas, $SCORE, $placedAntennas, $remainingAntennas, $reachedBuildings, $unreachedBuildings, $rewardGiven, $reward;

    // bigPixel
    $antenna->placed = true;
    $antenna->r = $r;
    $antenna->c = $c;
    $bigPixel = getBigPixel($antenna->r, $antenna->c);
    $bigPixel2antennas[$bigPixel[0]][$bigPixel[1]][$antenna->id] = $antenna;

    $placedAntennas[$antenna->id] = $antenna;
    unset($remainingAntennas[$antenna->id]);

    // scores
    $buildings = getNearBuildings($antenna->r, $antenna->c, $antenna->range);
    foreach ($buildings as $_building) {
        $building = $_building['building'];
        $score = calcScore($antenna, $building);
        if ($score > $building->score) {
            $deltaScore = $score - $building->score;
            $building->score = $score;
            $SCORE += $deltaScore;
            if ($deltaScore > 0 && $unreachedBuildings[$building->id]) {
                $reachedBuildings[$building->id] = $building;
                unset($unreachedBuildings[$building->id]);
            }
            if (!$rewardGiven && count($unreachedBuildings) == 0) {
                $rewardGiven = true;
                $SCORE += $reward;
            }
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

$unreachedBuildings = $BUILDINGS;
$remainingAntennas = $ANTENNAS;


/* REAL ALGO */
$orderedBuildings = $BUILDINGS;
usort($orderedBuildings, function (Building $b1, Building $b2) {
    return $b1->speed > $b2->speed;
});

$orderedAntennas = $ANTENNAS;
usort($orderedAntennas, function (Antenna $a1, Antenna $a2) {
    return $a1->speed > $a2->speed;
});

for ($i = 0; $i < $buildingsCount; $i++) {
    if (isset($orderedAntennas[$i]) && isset($orderedBuildings[$i])) {
        placeAntenna($orderedAntennas[$i], $orderedBuildings[$i]->r, $orderedBuildings[$i]->c);
    }
}

//$SCORE += $reward;

/* SCORING & OUTPUT */
Log::out("SCORE($fileName) = " . $SCORE);
$fileManager->outputV2(getOutput(), 'score_' . $SCORE);
