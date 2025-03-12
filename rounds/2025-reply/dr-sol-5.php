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



/** QUI DEVI SCRIVERE L'ALGORITMO */

// ------------------------------------------------------------------------------------
// STRUTTURE E VARIABILI GLOBALI DI SUPPORTO
// ------------------------------------------------------------------------------------

// In $placedResources[t] salviamo tutte le risorse acquistate al turno t (per output)
$placedResources = [];

// Dati sulle risorse possedute
// Ogni elemento:
//   'id', 'turnAcquired',
//   'activationCost', 'periodicCost',
//   'baseActive', 'baseDowntime',
//   'lifeCycle', 'baseBuildings',
//   'specialType', 'effectPercent'
$ownedResources = [];

// Budget, che può arrivare a 0 ma mai sotto
$capital = $initialCapital;

// Per tracciare accumulatori (E)
$accumulatorCapacity = 0;   // somma di RE di tutte le E ancora vive
$accumulatorStored   = 0;   // edifici virtuali accumulati

// Punteggio totale (somma dei profitti di tutti i turni)
$totalScore = 0;

// ------------------------------------------------------------------------------------
// FUNZIONI DI SUPPORTO
// ------------------------------------------------------------------------------------

/**
 * Ritorna true se la risorsa r è ancora “viva” al turno t
 * (cioè se l’età < lifeCycle).
 */
function isResourceInLife(int $turn, array $res): bool
{
    $age = $turn - $res['turnAcquired'];
    return ($age >= 0 && $age < $res['lifeCycle']);
}

/**
 * Ritorna true se la risorsa r è attiva (on) al turno t,
 * considerando i cicli di active/downtime.
 */
function isResourceActive(int $turn, array $res): bool
{
    if (!isResourceInLife($turn, $res)) {
        return false;
    }
    $age     = $turn - $res['turnAcquired'];
    $cLength = $res['baseActive'] + $res['baseDowntime'];
    $phase   = $age % $cLength;
    return ($phase < $res['baseActive']);
}

/**
 * Somma gli effetti percentuali di un certo tipo RT (A, B, D) tra tutte le risorse attive in un turno.
 * Es: 2 risorse A con +50% e -20% => sumA = 0.50 + (-0.20) = +0.30
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
 * Applica formula: newVal = floor( baseVal * (1 + sumPerc) ), clamp a >= 0
 */
function applyAdditivePercent(int $baseVal, float $sumPerc): int
{
    $val = (int) floor($baseVal * (1.0 + $sumPerc));
    return max($val, 0);
}

/**
 * Calcola quanti edifici alimentano le risorse attive in turno t,
 * applicando l'effetto A (somma percentuali di A).
 */
function getActivePoweredBuildings(int $turn): int
{
    global $ownedResources;

    // Somma percentuali A
    $sumA = getSumOfPerc('A', $turn);

    $total = 0;
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        // RU base
        $ruBase  = $res['baseBuildings'];
        // RU finale con effetto A
        $ruFinal = applyAdditivePercent($ruBase, $sumA);
        $total  += $ruFinal;
    }
    return $total;
}

/**
 * Applica l'effetto di tutte le risorse C attive al turno t
 * sulla lifeCycle di una nuova risorsa (acquistata in questo turno).
 * RL_final = floor(RL_base * (1 + sumC)), clamp >= 1
 */
function applyActiveCToLifeCycle(int $turn, int $baseRL): int
{
    global $ownedResources;
    // Somma le percentuali C
    $sumC = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === 'C' && isResourceActive($turn, $res)) {
            $sumC += ((float)$res['effectPercent']) / 100.0;
        }
    }
    $newRL = (int) floor($baseRL * (1.0 + $sumC));
    return max($newRL, 1);
}

/**
 * Aggiorna la capacità totale dell'accumulatore (E) e riduce
 * lo stored se la nuova capacity è inferiore.
 */
function updateAccumulatorCapacityAndStored(int $turn)
{
    global $ownedResources, $accumulatorCapacity, $accumulatorStored;

    $newCap = 0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] !== 'E') {
            continue;
        }
        if (isResourceInLife($turn, $res)) {
            // La "percentuale" delle E in realtà è la loro capacità
            $cap = (int)$res['effectPercent'];
            if ($cap > 0) {
                $newCap += $cap;
            }
        }
    }
    // Se scende, riduciamo lo stored
    if ($newCap < $accumulatorCapacity) {
        $accumulatorCapacity = $newCap;
        if ($accumulatorStored > $accumulatorCapacity) {
            $accumulatorStored = $accumulatorCapacity;
        }
    } else {
        $accumulatorCapacity = $newCap;
    }
}

/**
 * Tenta l'acquisto di una risorsa rId al turno t, se il capitale lo consente.
 */
function buyResource(int $rId, int $turn): bool
{
    global $resources, $capital, $ownedResources, $placedResources;

    $r = $resources[$rId];
    if ($capital < $r->activationCost) {
        return false; // non possiamo comprare
    }

    // Paghiamo
    $capital -= $r->activationCost;
    // Se andiamo sotto zero, settiamo a zero (ma di solito la condizione if() evita già questo caso).
    if ($capital < 0) {
        $capital = 0;
    }

    // Calcoliamo la RL definitiva (effetto C)
    $finalLife = applyActiveCToLifeCycle($turn, $r->lifeCycle);

    // Salviamo la risorsa
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

    // Per output
    $placedResources[$turn][] = $r->id;

    echo "Acquistata risorsa #{$r->id} al turno $turn\n";

    return true;
}

// ------------------------------------------------------------------------------------
// LOOP PRINCIPALE SUI TURNI
// ------------------------------------------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {

    // Aggiorniamo la capacità degli accumulatori per questo turno
    updateAccumulatorCapacityAndStored($t);

    // 1) Calcoliamo thresholds e TR con effetto B, D
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    $sumB = getSumOfPerc('B', $t);
    $sumD = getSumOfPerc('D', $t);

    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);
    $finalTR   = applyAdditivePercent($baseTR, $sumD);

    // 2) Paghiamo manutenzione delle risorse vive
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            $maintenanceCost += $res['periodicCost'];
        }
    }
    // Togliamo dal capitale (senza scendere sotto zero)
    $capital -= $maintenanceCost;
    if ($capital < 0) {
        $capital = 0;
    }

    // 3) Calcoliamo edifici alimentati dalle risorse attive
    $poweredByResources = getActivePoweredBuildings($t);

    // 4) Se non raggiungiamo finalMinB, proviamo ad acquistare risorse (greedy)
    if ($poweredByResources < $finalMinB) {
        // Ordiniamo le risorse in base a (activationCost / baseBuildings)
        $sortedIds = array_keys($resources);
        usort($sortedIds, function($a, $b) {
            global $resources;
            $ra = $resources[$a];
            $rb = $resources[$b];
            if ($ra->buildingsCapacity <= 0) {
                $valA = PHP_INT_MAX;
            } else {
                $valA = $ra->activationCost / $ra->buildingsCapacity;
            }
            if ($rb->buildingsCapacity <= 0) {
                $valB = PHP_INT_MAX;
            } else {
                $valB = $rb->activationCost / $rb->buildingsCapacity;
            }
            return $valA <=> $valB;
        });

        // Proviamo a comprare finché non superiamo finalMinB
        while ($poweredByResources < $finalMinB) {
            $bought = false;
            foreach ($sortedIds as $rid) {
                if (buyResource($rid, $t)) {
                    $poweredByResources = getActivePoweredBuildings($t);
                    $bought = true;
                    if ($poweredByResources >= $finalMinB) {
                        break;
                    }
                }
            }
            if (!$bought) {
                // Non siamo riusciti a comprare nulla, usciamo
                break;
            }
        }
    }

    // Ricalcoliamo alimentazione post-acquisti
    $poweredByResources = getActivePoweredBuildings($t);

    // 5) Gestione accumulatori (E)
    if ($poweredByResources > $finalMaxB) {
        // Surplus da stoccare
        $surplus = $poweredByResources - $finalMaxB;
        $free    = $accumulatorCapacity - $accumulatorStored;
        $store   = min($surplus, $free);
        $accumulatorStored += $store;
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        // Proviamo a coprire con accumulatore
        $need = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $need) {
            $accumulatorStored -= $need;
            $poweredByResources = $finalMinB;
        } else {
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // 6) Calcoliamo profit e aggiorniamo totalScore
    $profit = 0;
    if ($poweredByResources >= $finalMinB) {
        $effectivePowered = min($poweredByResources, $finalMaxB);
        $profit = $effectivePowered * $finalTR;
    }
    // Sommiamo al totalScore (che è ciò che vogliamo massimizzare)
    $totalScore += $profit;

    // Il capitale potrebbe anche andare a zero, ma non scendiamo sotto zero;
    // qui l'eventuale logica di "budget" non incide sul punteggio
    // Tuttavia, se vuoi tenerne traccia per debug:
    $capital += $profit;
    if ($capital < 0) {
        $capital = 0;
    }

    // Debug opzionale
    // echo "Turno=$t, powered=$poweredByResources, profit=$profit, cap=$capital, totalScore=$totalScore\n";
}

// Al termine, $placedResources è pronto per l'output e $totalScore è il profit totale
echo "TOTAL SCORE: $totalScore\n";




/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), $totalScore);
