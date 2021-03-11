<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'e';
$meanRange = 1;
Cerberus::runClient(['fileName' => 'b', 'meanRange' => 1.1]);
Autoupload::init();
include 'reader.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antennas */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$SCORE = 0;
$placedAntennas = 0;

/* FUNCTIONS */
/** @var Building $a */
/** @var Building $b */
function compareBuilding($a, $b) {
    return $a->latencyWeight < $b->latencyWeight;
}

function compareAntenna($a, $b) {
    return $a->range < $b->range && $a->speed < $b->speed;
}

function findBestCoordinates() {
    global $map, $W, $H;
    $max = 0;
    [$bestR, $bestC] = [0, 0];
    for ($c=0; $c < $W; $c++) { 
        for ($r=0; $r < $H; $r++) { 
            if ($map[$r][$c] > $max && $map[$r][$c] != -1) {
                $max = $map[$r][$c];
                [$bestR, $bestC] = [$r, $c];
            }
        }   
    }

    return [$bestR, $bestC];
}


/* ALGO */
// Ordino edifici per speed alta e antenna per speed alta.
usort($buildings, "compareBuilding");
usort($antennas, "compareAntenna");

$map = [];
for ($c=0; $c < $W; $c++) { 
    for ($r=0; $r < $H; $r++) { 
        $map[$r][$c] = 0;
    }   
}
    
foreach($buildings as $building) {
    $start = [$building->r - $meanRange, $building->c - $meanRange];
    $end = [$building->r + $meanRange, $building->c + $meanRange];
    
    for ($r = $start[0]; $r < $end[0]; $r++) {
        for ($c = $start[1]; $c < $end[1]; $c++) {
            $map[$r][$c]+= $building->speedWeight;            
        }
    }     
}

$remain = count($antennas);
$placedAntennas = 0;
foreach($antennas as $antenna) {        
    Log::out("Piazzando antenna, rimangono: " . $remain);
    [$bestR, $bestC] = findBestCoordinates();
    $antenna->r = $bestR;
    $antenna->c = $bestC;
    // Svuoto le coordinate vicine
    $start = [$antenna->r - $antenna->range, $antenna->c - $antenna->range];
    $end = [$antenna->r + $antenna->range, $antenna->c + $antenna->range];
    
    for ($r = $start[0]; $r < $end[0]; $r++) {
        for ($c = $start[1]; $c < $end[1]; $c++) {
            $map[$r][$c] = 0;            
        }
    }     
    $map[$antenna->r][$antenna->c] = -1;

    $remain--;
    $placedAntennas ++;
}

/* SCORING & OUTPUT */
$output = $placedAntennas . PHP_EOL;
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
    }
}

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output, 'mean' . $meanRange);
