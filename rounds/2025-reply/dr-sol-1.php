<?php

use JMGQ\AStar\DomainLogicInterface;
use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use JMGQ\AStar\AStar;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;

/* Config & Pre runtime */
$fileName = '8';
$_visualyze = false;
$_analyze = false;
//$param1 = 1;
//Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

/* Reader */
include_once 'reader.php';

/* Classes */

/** @var int $initialCapital */
global $initialCapital;
/** @var int $resourcesCount */
global $resourcesCount;
/** @var int $turnsCount */
global $turnsCount;
/** @var Turn[] $turns */
global $turns;
/** @var Resource[] $resources */
global $resources;

// FX
function getOutput() {
    global $placedResources; // [$t] => [... lista ID]
    $output = [];
    ksort($placedResources);
    foreach($placedResources as $time => $resources) {
        $_output = [];
        $_output[] = $time;
        $_output[] = count($resources);
        foreach($resources as $r)
            $_output[] = $r;
        $output[] = implode(" ", $_output);
    }
    return implode("\n", $output);
}

function place($id, $t) {
    global $resources, $placedResources, $targetShape, $resource2shape;
    echo "Piazzo $id @ $t\n";
    $placedResources[$t][] = $id;
    foreach($resource2shape[$id] as $_t => $delta) {
        $targetShape[$t+$_t] = max(0, $targetShape[$t+$_t]-$delta);
    }
    unset($resources[$id]);
}

// RUN
$SCORE = 0;
Log::out("Run started...");

$coefficientShape = 0.8;
$targetShape = [];

$resource2shape = [];

foreach($turns as $k => $turn) {
    $targetShape[$k] = ceil($turn->maxBuildings*$coefficientShape);
}

foreach($resources as $r) {
    $shape = [];
    for($i=0;$i<$r->lifeCycle;$i++) {
        $shape[$i] = ($i%($r->activeTurns+$r->downtimeTurns)) < $r->activeTurns ? $r->buildingsCapacity : 0;
    }
    $resource2shape[$r->id] = $shape;
}




/** QUI DEVI SCRIVERE L'ALGORITMO:
 * devi ottimizzare il placing delle risorse non ancora piazzate, sapendo la loro forma ($resource2shape[<id risorsa>])
 * e sapendo che devi il più possibile abbassare uniformemente la $targetShape
 * per piazzare usa la funzione place(<id risorsa>, <turno, partendo da 0>);
 * */



/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), "unknown");
