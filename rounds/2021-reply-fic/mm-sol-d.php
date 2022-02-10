<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

/* CONFIG */
$fileName = 'd';
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

define('bigPixelSize', 8); // TUNE THIS

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
            if ($deltaScore >= 0 && $unreachedBuildings[$building->id]) {
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
function getCluster(&$bigPixel, &$putIntoCluster = null)
{
    global $BIG_PIXELS, $W, $H;
    if ($bigPixel['visited']) return;
    $bigPixel['visited'] = true;
    if (count($bigPixel['buildings']) === 0) return;
    $putIntoCluster[] = $bigPixel;
    if ($bigPixel['r'] > 0) {
        getCluster($BIG_PIXELS[$bigPixel['r'] - 1][$bigPixel['c']], $putIntoCluster);
    }
    if ($bigPixel['r'] < $H - 1) {
        getCluster($BIG_PIXELS[$bigPixel['r'] + 1][$bigPixel['c']], $putIntoCluster);
    }
    if ($bigPixel['c'] > 0) {
        getCluster($BIG_PIXELS[$bigPixel['r']][$bigPixel['c'] - 1], $putIntoCluster);
    }
    if ($bigPixel['c'] < $W - 1) {
        getCluster($BIG_PIXELS[$bigPixel['r']][$bigPixel['c'] + 1], $putIntoCluster);
    }
}

$BIG_PIXELS = [];
for ($r = 0; $r < ceil($H / bigPixelSize); $r++) {
    for ($c = 0; $c < ceil($W / bigPixelSize); $c++) {
        $BIG_PIXELS[$r][$c] = [
            'r' => $r,
            'c' => $c,
            'buildings' => $bigPixel2buildings[$r][$c] ?? [],
            'visited' => false,
        ];
    }
}

$CLUSTERS = [];
for ($r = 0; $r < ceil($H / bigPixelSize); $r++) {
    for ($c = 0; $c < ceil($W / bigPixelSize); $c++) {
        $bigPixel = $BIG_PIXELS[$r][$c];
        if (count($bigPixel['buildings']) > 0 && !$bigPixel['visited']) {
            $cluster = [];
            getCluster($bigPixel, $cluster);
            $CLUSTERS[] = $cluster;
        }
    }
}

$GROUPS = [];
foreach ($CLUSTERS as $cluster) {
    $group = [];
    $top = $H;
    $bottom = -1;
    $left = $W;
    $right = -1;
    foreach ($cluster as $bigPixel) {
        foreach ($bigPixel['buildings'] as $building) {
            /** @var Building $building */
            $group[] = $building;
            if ($building->r < $top) $top = $building->r;
            if ($building->r > $bottom) $bottom = $building->r;
            if ($building->c < $left) $left = $building->c;
            if ($building->c > $right) $right = $building->c;
        }
    }
    $GROUPS[] = [
        'buildings' => $group,
        'top' => $top,
        'left' => $left,
        'bottom' => $bottom,
        'right' => $right,
        'width' => $right - $left + 1,
        'height' => $bottom - $top + 1,
    ];
}
usort($GROUPS, function ($g1, $g2) {
    return $g1['width'] * $g1['height'] < $g2['width'] * $g2['height'];
});

$rangeZeroAntennas = collect($ANTENNAS)->filter(function (Antenna $a) {
    return $a->range === 0;
})->toArray();
$ANTENNAS = collect($ANTENNAS)->filter(function (Antenna $a) {
    return $a->range !== 0;
})->toArray();

$ANTENNAS = collect($ANTENNAS)->sortByDesc('speed')->toArray();

// Angoli
$speedAntennas = array_splice($ANTENNAS, 0, 4 * count($GROUPS));
$speedAntennas = collect($speedAntennas)->sortByDesc('range')->toArray();

$centerFactor = 8;
$aIdx = 0;
foreach ($GROUPS as $g) {
    $top = min(max(0, $g['top'] + floor($g['height'] / $centerFactor)), $H);
    $left = min(max(0, $g['left'] + floor($g['width'] / $centerFactor)), $W);
    $bottom = min(max(0, $g['bottom'] - floor($g['height'] / $centerFactor)), $H);
    $right = min(max(0, $g['right'] - floor($g['width'] / $centerFactor)), $W);
    placeAntenna($speedAntennas[$aIdx], $top, $left);
    placeAntenna($speedAntennas[$aIdx + 1], $bottom, $right);
    placeAntenna($speedAntennas[$aIdx + 2], $bottom, $left);
    placeAntenna($speedAntennas[$aIdx + 3], $top, $right);
    $aIdx += 4;
}

// Lato
$centerFactor = 8;
$speedAntennas = array_splice($ANTENNAS, 0, 4 * count($GROUPS));
$speedAntennas = collect($speedAntennas)->sortByDesc('range')->toArray();

$aIdx = 0;
foreach ($GROUPS as $g) {
    $top = min(max(0, $g['top'] + floor($g['height'] / $centerFactor)), $H);
    $left = min(max(0, $g['left'] + floor($g['width'] / $centerFactor)), $W);
    $bottom = min(max(0, $g['bottom'] - floor($g['height'] / $centerFactor)), $H);
    $right = min(max(0, $g['right'] - floor($g['width'] / $centerFactor)), $W);
    placeAntenna($speedAntennas[$aIdx], $top, $g['left'] + floor($g['width'] / 2));
    placeAntenna($speedAntennas[$aIdx + 1], $g['top'] + floor($g['height'] / 2), $g['left'] + floor($g['width'] / 2));
    placeAntenna($speedAntennas[$aIdx + 2], $g['top'] + floor($g['height'] / 2), $left);
    placeAntenna($speedAntennas[$aIdx + 3], $top, $right);
    $aIdx += 4;
}

// In mezzo
$speedAntennas = array_splice($ANTENNAS, 0, 4 * count($GROUPS));
$speedAntennas = collect($speedAntennas)->sortByDesc('range')->toArray();

$centerFactor = 4;
$aIdx = 0;
foreach ($GROUPS as $g) {
    $top = min(max(0, $g['top'] + floor($g['height'] / $centerFactor)), $H);
    $left = min(max(0, $g['left'] + floor($g['width'] / $centerFactor)), $W);
    $bottom = min(max(0, $g['bottom'] - floor($g['height'] / $centerFactor)), $H);
    $right = min(max(0, $g['right'] - floor($g['width'] / $centerFactor)), $W);
    placeAntenna($speedAntennas[$aIdx], $top, $left);
    placeAntenna($speedAntennas[$aIdx + 1], $bottom, $right);
    placeAntenna($speedAntennas[$aIdx + 2], $bottom, $left);
    placeAntenna($speedAntennas[$aIdx + 3], $top, $right);
    $aIdx += 4;
}

//$SCORE += $reward;

/* SCORING & OUTPUT */
Log::out("SCORE($fileName) = " . $SCORE);
$fileManager->outputV2(getOutput(), 'score_' . $SCORE);
