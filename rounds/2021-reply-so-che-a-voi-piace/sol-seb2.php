<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = 'b';

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


/*
for($riga = 0; $riga < $H; $riga ++)
{
    for($colonna = 0; $colonna < $W; $colonna ++) {

    }
}
*/
$placedAntennas = 0;

/*
foreach($buildings as $building) {
    $building->localScore = $building->speedWeight;
}
*/


/* FUNCTIONS */
/** @var Building $a */
/** @var Building $b */
function compareBuilding($a, $b) {
    return $a->speedWeight > $b->speedWeight;
}

function compareAntenna($a, $b) {
    return $a->speed > $b->speed;
}


/* ALGO */
// Ordino edifici per speed alta e antenna per speed alta.
usort($buildings, "compareBuilding");
usort($antennas, "compareAntenna");


$nearestOrder = [];

$lontaniOrder = [];

$copiaAntenne = $antennas;

$keys = array_keys($copiaAntenne);
array_multisort(
    array_column($copiaAntenne, 'range'), SORT_DESC, SORT_NUMERIC, $copiaAntenne, $keys
);
$antennasOrderedBySpeedDesc = array_combine($keys, $copiaAntenne);

$piazzatiLog = 1;

foreach($buildings as $building) {
    if(!$building->covered) {
        $bestAntenna = getBestAntennaForBuilding($building);
        if ($bestAntenna) {
            unset($antennasOrderedBySpeedDesc[$bestAntenna->id]);
            Log::out('Piazzo num: ' . $piazzatiLog);
            $antennas[$bestAntenna->id]->c = (int)$building->c;
            $antennas[$bestAntenna->id]->r = (int)$building->r;
            $building->covered = true;
            $piazzatiLog ++;
        }
        else {
            Log::out('Non esiste una antenna per questo');
        }
    }
}


function getBestAntennaForBuilding($building)
{
    global $antennasOrderedBySpeedDesc, $antennas;

    $bestAntenna = null;

    $lastLocalScore = 0;
    $lastAntennaId = 0;

    foreach($antennas as $id => $localAntenna)
    {
        if($localAntenna->placed) continue;
        $localScore = $localAntenna->score($building, $building->r, $building->c);
        if($localScore === 0 || $lastLocalScore < $localScore) {
            $lastLocalScore = $localScore;
            $lastAntennaId = $localAntenna->id;
        }

        $range = (int)$antennas[$localAntenna->id]->range;

        $horizontalRightMaxValueX = (int)($building->c + $range);
        $horizontalRightMaxValueY = (int)$building->r;

        $HorizontalLeftBottomMaxValueX = (int)($building->c - $range);
        $HorizontalLeftBottomMaxValueY = (int)$building->r;

        $VerticalBottomMaxValueX = (int)($building->c);
        $VerticalBottomMaxValueY = (int)($building->r + $range);

        $VerticalTopMaxValueX = (int)$building->c;
        $VerticalTopMaxValueY = (int)($building->r - $range);

        // se è già piazzata la salto
        if($antennas[$id]->r && $antennas[$id]->c) {
            // Controllo se copre il mio edifico attuale
            $rAntenna = $antennas[$id]->r;
            $cAntenna = $antennas[$id]->c;

            if(
                distance($horizontalRightMaxValueX, $horizontalRightMaxValueY, $cAntenna, $rAntenna) === $range ||
                distance($HorizontalLeftBottomMaxValueX, $HorizontalLeftBottomMaxValueY, $cAntenna, $rAntenna) === $range ||
                distance($VerticalBottomMaxValueX, $VerticalBottomMaxValueY, $cAntenna, $rAntenna) === $range ||
                distance($VerticalTopMaxValueX, $VerticalTopMaxValueY, $cAntenna, $rAntenna) === $range
            ) {
                // edificio già coperto da una antenna
                Log::out('edificio #' . $building->id . ' coperto da antenna: #' . $localAntenna->id);
                continue;
            }

            Log::out('Gia piazzata salto');
            continue;
        }
    }

    if($lastAntennaId) {
        $bestAntenna = $antennas[$lastAntennaId];
    }

    return $bestAntenna;
}


/*
function calculateDistanceBetweenPoints($x1, $y1, $x2, $y2)
{
    return sqrt(pow($x2  -  $x1, 2) + pow($y2 - $y1,2));
}*/

/*
foreach($nearestOrder as $building) {
    $bestAntenna = getBestAntennaForBuilding($building);

    if($bestAntenna) {
        unset($antennasOrderedBySpeedDesc[$bestAntenna->id]);
        Log::out('Piazzo vicina');
        $antennas[$bestAntenna->id]->c = (int)$building->c;
        $antennas[$bestAntenna->id]->r = (int)$building->r;
    }
}

foreach($lontaniOrder as $building) {
    $bestAntenna = getBestAntennaForBuilding($building);

    if($bestAntenna) {
        Log::out('Piazzo lontana');
        unset($antennasOrderedBySpeedDesc[$bestAntenna->id]);
        $antennas[$bestAntenna->id]->c = (int)$building->c;
        $antennas[$bestAntenna->id]->r = (int)$building->r;
    }
}
*/


$numPlacedAntennas = 0;
$output = "";
foreach ($antennas as $antenna) {
    if($antenna->placed()) {
        $numPlacedAntennas++;
        $output .= $antenna->id . " " . $antenna->c . " " . $antenna->r . PHP_EOL;
        Log::out('placed');
    } else {
        Log::out("Antenna not placed");
    }
}
$output = $numPlacedAntennas . PHP_EOL . $output;

Log::out("SCORE($fileName) = ");
$fileManager->outputV2($output);



