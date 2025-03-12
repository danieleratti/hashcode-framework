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


/** QUI DEVI SCRIVERE L'ALGORITMO
 * */

/** QUI DEVI SCRIVERE L'ALGORITMO
 * */

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

$placedResources = []; // Inizializza l'array delle risorse piazzate
$budget = $initialCapital;
$activeResources = [];  // Risorse attive in un dato turno:  [turn => [resourceId => resourceData]]
$accumulator = 0; // Tiene traccia dell'accumulatore

// Funzioni di utilità

function calculatePoweredBuildings($turn, $activeResources, &$accumulator) {
    global $turns;
    $totalBuildings = 0;
    $turnData = $turns[$turn];

    foreach ($activeResources[$turn] as $resource) {

        $buildingsPowered = $resource->buildingsCapacity;

        // Applica effetti di tipo A (Smart Meter)
        foreach($activeResources[$turn] as $effectResource) {
            if($effectResource->specialEffect == 'A') {
                if($effectResource->percentage > 0) {
                    $buildingsPowered = floor($buildingsPowered + ($effectResource->percentage / 100) * $buildingsPowered);
                }
            }
        }


        $totalBuildings += $buildingsPowered;
    }

    // Applica effetti di tipo B (Distribution Facility).  Va fatto DOPO aver calcolato i buildings
    $minBuildings = $turnData->minBuildings;
    $maxBuildings = $turnData->maxBuildings;
    foreach($activeResources[$turn] as $effectResource){
        if($effectResource->specialEffect == 'B'){
            if($effectResource->percentage > 0){
                $minBuildings = floor($minBuildings + ($effectResource->percentage/100)*$minBuildings);
                $maxBuildings = floor($maxBuildings + ($effectResource->percentage/100)*$maxBuildings);

            }else{
                $minBuildings = floor($minBuildings + ($effectResource->percentage/100)*$minBuildings);
                $maxBuildings = floor($maxBuildings + ($effectResource->percentage/100)*$maxBuildings);
                $minBuildings = max(0, $minBuildings);
                $maxBuildings = max(0, $maxBuildings);
            }

        }
    }


    // Gestione Accumulatore (E)
    if ($totalBuildings > $maxBuildings) {
        foreach($activeResources[$turn] as $effectResource){
            if($effectResource->specialEffect == 'E'){
                $accumulator += ($totalBuildings - $maxBuildings);
            }
        }

        $totalBuildings = $maxBuildings;
    }


    if ($totalBuildings < $minBuildings) {
        $needed = $minBuildings - $totalBuildings;

        foreach($activeResources[$turn] as $effectResource){
            if($effectResource->specialEffect == 'E'){
                if ($accumulator >= $needed) {
                    $accumulator -= $needed;
                    $totalBuildings = $minBuildings;
                }
            }
        }
    }


    return $totalBuildings;
}



function calculateProfit($turn, $totalBuildings)
{
    global $turns, $activeResources;
    $turnData = $turns[$turn];
    $profitPerBuilding = $turnData->profitPerBuilding;
    // Applica effetti di tipo D
    foreach($activeResources[$turn] as $effectResource) {
        if($effectResource->specialEffect == 'D') {
            if($effectResource->percentage > 0) { // Green
                $profitPerBuilding = floor($profitPerBuilding + ($effectResource->percentage/100) * $profitPerBuilding);
            }else{
                $profitPerBuilding = floor($profitPerBuilding + ($effectResource->percentage/100) * $profitPerBuilding);
                $profitPerBuilding = max(0,$profitPerBuilding);
            }

        }
    }
    return min($totalBuildings, $turns[$turn]->maxBuildings) * $profitPerBuilding;
}


function updateResourceLifespan(&$resource, $turn, $activeResources) {
    global $resources;

    foreach($activeResources[$turn] as $effectResource){
        if ($effectResource->specialEffect == 'C') {
            if($effectResource->percentage >0){ //green
                $resource->lifeCycle = floor($resource->lifeCycle + ($effectResource->percentage / 100) * $resource->lifeCycle );

            }else{ //non green
                $resource->lifeCycle = floor($resource->lifeCycle + ($effectResource->percentage / 100) * $resource->lifeCycle);
                $resource->lifeCycle = max(1,$resource->lifeCycle);
            }
        }
    }
}


// Ciclo principale dei turni
for ($t = 0; $t < $turnsCount; $t++) {

    $turnData = $turns[$t];
    $minBuildings = $turnData->minBuildings;


    // 0.  Inizializza le risorse attive per questo turno, copiando quelle del turno precedente (se esistono)
    if($t > 0) {
        $activeResources[$t] = $activeResources[$t - 1];
    }

    // 1.  Acquisto risorse

    // Strategia di acquisto:  Prioritizza risorse con buon rapporto costo/beneficio e che soddisfino i requisiti.
    // Ordina le risorse per un qualche criterio di convenienza.  Potrebbe essere RU/RA, o una combinazione di fattori.
    $sortedResources = $resources;

    //Simple sorting by cost/building capacity
    uasort($sortedResources, function($a, $b) {
        return ($a->buildingsCapacity / $a->activationCost) <=> ($b->buildingsCapacity / $b->activationCost);
    });

    $purchasedResources = []; //tiene traccia delle risorse acquistate in questo specifico turno

    foreach ($sortedResources as $resource) {
        if ($budget >= $resource->activationCost) {

            $purchasedResources[] = $resource;  // Aggiungi alla lista delle risorse acquistate
            $placedResources[$t][] = $resource->id;  //Registra l'ID
            $budget -= $resource->activationCost;
            $activeResources[$t][$resource->id] = clone $resources[$resource->id]; // Clona la risorsa
            updateResourceLifespan($activeResources[$t][$resource->id], $t, $activeResources); //Applica effetto C

            unset($resources[$resource->id]); // Rimuovi dalle risorse disponibili, poiche' ne possiamo comprare un numero illimitato. Ma lo rimuovo SOLO se lo compro.
        }
    }


    // 2. Calcola edifici alimentati e costi periodici

    $poweredBuildings = calculatePoweredBuildings($t, $activeResources, $accumulator);
    $maintenanceCost = 0;

    // Calcola il costo di maintenance *prima* di rimuovere le risorse scadute.

    if(isset($activeResources[$t])){
        foreach ($activeResources[$t] as $resourceId => $resource) {
            $maintenanceCost += $resource->periodicCost;
        }
    }

    // 3. Calcola il profitto del turno

    $profit = 0;
    if ($poweredBuildings >= $minBuildings) {
        $profit = calculateProfit($t, $poweredBuildings);
    }


    // 4. Aggiorna budget
    $budget += $profit - $maintenanceCost;

    // Rimuovi le risorse che sono arrivate a fine vita *DOPO* aver calcolato il profitto.

    if(isset($activeResources[$t])){
        foreach ($activeResources[$t] as $resourceId => $resource) {

            //Gestione vita delle risorse
            $resource->lifeCycle -= 1;

            if ($resource->lifeCycle <= 0) {
                unset($activeResources[$t][$resourceId]);
            }

        }
    }

    //Log di debug
    //echo "Turno $t: Budget=$budget, Edifici=$poweredBuildings, Profitto=$profit, Accumulatore=$accumulator\n";

}


/** FINE ALGORITMO */


/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), 'Unknown');
