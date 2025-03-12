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



/** QUI DEVI SCRIVERE L'ALGORITMO */

// ---------------------------------------------------------------------------
// STRUTTURE DI SUPPORTO
// ---------------------------------------------------------------------------

// In $placedResources[t] memorizziamo le risorse acquistate al turno t (per output).
$placedResources = [];

// Informazioni sulle risorse possedute:
// Ciascun elemento sarà un array con:
//   'id', 'turnAcquired', 'activationCost', 'periodicCost',
//   'baseActive', 'baseDowntime', 'lifeCycle', 'baseBuildings',
//   'specialType', 'effectPercent'
$ownedResources = [];

// Capitale (può arrivare a 0, ma mai sotto)
$capital = $initialCapital;

// Gestione accumulatore (E)
$accumulatorCapacity = 0;  // somma delle capacity di tutte le E ancora vive
$accumulatorStored   = 0;  // quanti "edifici virtuali" abbiamo accumulato

// Punteggio finale: somma delle revenue (edifici alimentati * TR) ogni volta che si raggiunge minB
$totalScore = 0;

// ---------------------------------------------------------------------------
// FUNZIONI DI SUPPORTO
// ---------------------------------------------------------------------------

/**
 * Ritorna true se la risorsa r è ancora in vita al turno t (age < lifeCycle).
 */
function isResourceInLife(int $turn, array $res): bool
{
    $age = $turn - $res['turnAcquired'];
    return ($age >= 0 && $age < $res['lifeCycle']);
}

/**
 * Ritorna true se la risorsa r è "attiva" (produce RU ed effetti A,B,D,E) nel turno t.
 * Ciò accade se è viva e non in downtime.
 */
function isResourceActive(int $turn, array $res): bool
{
    if (!isResourceInLife($turn, $res)) {
        return false;
    }
    $age = $turn - $res['turnAcquired'];
    $cycleLen = $res['baseActive'] + $res['baseDowntime'];
    $phase = $age % $cycleLen;
    return ($phase < $res['baseActive']);
}

/**
 * Somma delle percentuali di un tipo (A, B, D) tra tutte le risorse attive.
 * Esempio: 2 risorse A +50%, -20% => sum = +30% => 0.30
 */
function getSumOfPerc(string $type, int $turn): float
{
    global $ownedResources;
    $sum = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === $type && isResourceActive($turn, $res)) {
            $sum += ((float)$res['effectPercent']) / 100.0;
        }
    }
    return $sum;
}

/**
 * newVal = floor( baseVal * (1 + sumPerc) ), clamp a 0.
 */
function applyAdditivePercent(int $baseVal, float $sumPerc): int
{
    $val = (int) floor($baseVal * (1.0 + $sumPerc));
    return max($val, 0);
}

/**
 * Calcola quanti edifici totali alimentano le risorse attive, considerando l'effetto A.
 */
function getActivePoweredBuildings(int $turn): int
{
    global $ownedResources;

    $sumA = getSumOfPerc('A', $turn);

    $total = 0;
    foreach ($ownedResources as $r) {
        if (!isResourceActive($turn, $r)) {
            continue;
        }
        $ruBase  = $r['baseBuildings'];
        $ruFinal = applyAdditivePercent($ruBase, $sumA);
        $total  += $ruFinal;
    }
    return $total;
}

/**
 * Applica l'effetto C (maintenance plan) al lifeCycle al momento dell'acquisto.
 * RL_final = floor( baseRL * (1 + sumC ) ), clamp a >=1.
 */
function applyActiveCToLifeCycle(int $turn, int $baseRL): int
{
    global $ownedResources;
    $sumC = 0.0;
    foreach ($ownedResources as $r) {
        if ($r['specialType'] === 'C' && isResourceActive($turn, $r)) {
            $sumC += ((float)$r['effectPercent']) / 100.0;
        }
    }
    $val = (int) floor($baseRL * (1.0 + $sumC));
    return max($val, 1);
}

/**
 * Ricalcola la capacity globale degli accumulatori E, scartando eccesso se diminuisce.
 */
function updateAccumulatorCapacityAndStored(int $turn)
{
    global $ownedResources, $accumulatorCapacity, $accumulatorStored;

    $newCap = 0;
    foreach ($ownedResources as $r) {
        if ($r['specialType'] !== 'E') {
            continue;
        }
        if (isResourceInLife($turn, $r)) {
            $cap = (int)$r['effectPercent']; // "percent" di E è in realtà la capacità
            if ($cap > 0) {
                $newCap += $cap;
            }
        }
    }
    if ($newCap < $accumulatorCapacity) {
        // riduciamo lo stored se necessario
        $accumulatorCapacity = $newCap;
        if ($accumulatorStored > $accumulatorCapacity) {
            $accumulatorStored = $accumulatorCapacity;
        }
    } else {
        $accumulatorCapacity = $newCap;
    }
}

/**
 * Tenta l'acquisto di UN insieme di risorse in un "batch" (somma costi <= capital).
 * Se non possiamo permettercele tutte, l'acquisto è invalido e non compra nulla.
 * Ritorna true se ha comprato, false se nulla.
 */
function buyResourcesBatch(array $chosenIds, int $turn): bool
{
    global $resources, $capital, $ownedResources, $placedResources;

    // Calcolo la somma dei costi di attivazione
    $sumCost = 0;
    foreach ($chosenIds as $rid) {
        $sumCost += $resources[$rid]->activationCost;
    }

    // Se supera il capitale, acquisto invalido
    if ($sumCost > $capital) {
        return false;
    }

    // Altrimenti compriamo tutto in blocco
    $capital -= $sumCost;
    if ($capital < 0) {
        $capital = 0;
    }

    // Registra in placedResources
    $placedResources[$turn] = $chosenIds;

    // Creiamo le entry in $ownedResources
    foreach ($chosenIds as $rid) {
        $r = $resources[$rid];
        // Applica effetto C per la lifeCycle
        $finalLife = applyActiveCToLifeCycle($turn, $r->lifeCycle);

        $ownedResources[] = [
            'id'             => $r->id,
            'turnAcquired'   => $turn,
            'activationCost' => $r->activationCost,
            'periodicCost'   => $r->periodicCost,
            'baseActive'     => $r->activeTurns,
            'baseDowntime'   => $r->downtimeTurns,
            'lifeCycle'      => $finalLife,
            'baseBuildings'  => $r->buildingsCapacity,
            'specialType'    => $r->specialEffect,
            'effectPercent'  => $r->percentage,
        ];

        echo "Acquistata risorsa #{$r->id} al turno $turn\n";
    }

    return true;
}

// ---------------------------------------------------------------------------
// LOOP PRINCIPALE (SECONDO LA SEQUENZA UFFICIALE)
// ---------------------------------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {

    // (1) START OF TURN: decidiamo quali risorse comprare in un unico "batch"

    // Esempio di strategia GREEDY: finché c'è capitale, pickiamo la risorsa più efficiente
    // e accumuliamo in un batch. Poi, se la somma totale sfora il budget, annulliamo tutto.
    // Invece di farla complicata, facciamo una logica semplice di "prendi la MIGLIORE" e basta
    // (o addirittura nessuna). Puoi personalizzare a piacere.

    // a) ordiniamo tutte le risorse per (buildingsCapacity / activationCost) decrescente
    // e proviamo a comprare LA prima
    $sortedAll = array_keys($resources);
    usort($sortedAll, function($a, $b) {
        global $resources;
        $ra = $resources[$a];
        $rb = $resources[$b];
        $effA = ($ra->activationCost>0) ? $ra->buildingsCapacity / $ra->activationCost : 999999;
        $effB = ($rb->activationCost>0) ? $rb->buildingsCapacity / $rb->activationCost : 999999;
        return ($effB <=> $effA); // desc
    });

    $chosenBatch = [];
    if (!empty($sortedAll)) {
        // prendiamo la "migliore"
        $bestId = $sortedAll[0];
        $chosenBatch = [$bestId];
    }

    // Ora tentiamo di comprare l'intero batch "chosenBatch"
    $acquistoOK = false;
    if (!empty($chosenBatch)) {
        $acquistoOK = buyResourcesBatch($chosenBatch, $t);
    }
    // se l'acquisto non riesce, restiamo con nulla comprato a questo turno

    // (2) PERIODIC COSTS
    updateAccumulatorCapacityAndStored($t); // se l'acquisto di E è riuscito, capacity potrebbe crescere
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            $maintenanceCost += $res['periodicCost'];
        }
    }
    $capital -= $maintenanceCost;
    if ($capital < 0) {
        $capital = 0;
    }

    // (3) TURN PROFIT: calcoliamo edifici alimentati (dopo l'acquisto e dopo manutenzione).
    // Effetto B e D sul turno
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    $sumB = getSumOfPerc('B', $t);
    $sumD = getSumOfPerc('D', $t);

    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);
    $finalTR   = applyAdditivePercent($baseTR, $sumD);

    // Calcolo edifici alimentati dalle risorse attive
    $poweredByResources = getActivePoweredBuildings($t);

    // Gestione accumulatore E: se c'è surplus, immagazziniamo; se c'è deficit, attingiamo
    if ($poweredByResources > $finalMaxB) {
        $surplus = $poweredByResources - $finalMaxB;
        $freeCap = $accumulatorCapacity - $accumulatorStored;
        $toStore = min($surplus, $freeCap);
        $accumulatorStored += $toStore;
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        $needed = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $needed) {
            $accumulatorStored -= $needed;
            $poweredByResources = $finalMinB;
        } else {
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // Se non raggiungiamo min, profit=0, altrimenti = min(powered, max)* finalTR
    $profit = 0;
    if ($poweredByResources >= $finalMinB) {
        $profit = min($poweredByResources, $finalMaxB) * $finalTR;
    }

    // (4) BUDGET UPDATE e TOTAL SCORE
    // Secondo l’esempio ufficiale, aggiungiamo la revenue al budget (senza sottrarre nulla dal punteggio)
    $capital += $profit;
    if ($capital < 0) {
        $capital = 0; // clamp
    }

    // Il totalScore è la somma delle revenue se min è soddisfatto (come dice l'esempio)
    $totalScore += $profit;
}

// ---------------------------------------------------------------------------
// A fine simulazione, stampiamo il totalScore e generiamo l’output con getOutput()
// ---------------------------------------------------------------------------
echo "TOTAL SCORE: $totalScore\n";




/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), $totalScore);
