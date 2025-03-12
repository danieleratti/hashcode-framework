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

// ------------------------------------------------------------------------------------
// STRUTTURE E VARIABILI GLOBALI DI SUPPORTO
// ------------------------------------------------------------------------------------

// Mappatura per output: in placedResources[t] salviamo tutte le risorse acquistate al turno t
$placedResources = [];

// Archivio delle risorse possedute, per calcoli di manutenzione, cicli di vita, ecc.
// Ogni voce avrà chiavi:
//   'id', 'turnAcquired', 'activationCost', 'periodicCost', 'baseActive', 'baseDowntime',
//   'lifeCycle', 'baseBuildings', 'specialType', 'effectPercent'
$ownedResources = [];

// Budget disponibile (modificato a ogni turno)
$capital = $initialCapital;

// Variabili per gestione Accumulatori E
$accumulatorCapacity = 0;  // somma di RE di tutte le risorse E attive e vive
$accumulatorStored   = 0;  // edifici "virtuali" accumulati

// Variabile per tracciare il punteggio complessivo
$totalScore = 0;

// ------------------------------------------------------------------------------------
// FUNZIONI DI SUPPORTO
// ------------------------------------------------------------------------------------

/**
 * Ritorna true se una risorsa è dentro il proprio lifeCycle.
 */
function isResourceInLife(int $turn, array $res): bool
{
    $age = $turn - $res['turnAcquired'];
    return ($age >= 0 && $age < $res['lifeCycle']);
}

/**
 * Ritorna true se la risorsa è attiva in un determinato turno
 * (cioè se non è scaduta e se non è in downtime).
 */
function isResourceActive(int $turn, array $res): bool
{
    if (!isResourceInLife($turn, $res)) {
        return false;
    }
    $age     = $turn - $res['turnAcquired'];
    $cLength = $res['baseActive'] + $res['baseDowntime'];
    $phase   = $age % $cLength;
    return ($phase < $res['baseActive']); // se siamo entro i RW turni, è on
}

/**
 * Ricalcola la capacità degli accumulatori (E) e riduce se necessario
 * lo stored quando scade una parte di capacity.
 */
function updateAccumulatorCapacityAndStored(int $turn)
{
    global $ownedResources, $accumulatorCapacity, $accumulatorStored;

    // Ricalcoliamo la capacity in base a tutte le E ancora in vita e attive/inattive
    // (purché l’età < lifeCycle).
    $newCapacity = 0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] !== 'E') {
            continue;
        }
        $age = $turn - $res['turnAcquired'];
        if ($age >= 0 && $age < $res['lifeCycle']) {
            // effectPercent di E è la capacità
            $cap = (int)$res['effectPercent'];
            if ($cap > 0) {
                $newCapacity += $cap;
            }
        }
    }

    // Se la nuova capacity è minore, riduciamo lo stored in eccesso
    if ($newCapacity < $accumulatorCapacity) {
        $delta = $accumulatorCapacity - $newCapacity;
        $accumulatorCapacity = $newCapacity;
        if ($accumulatorStored > $accumulatorCapacity) {
            $accumulatorStored = $accumulatorCapacity;
        }
    } else {
        $accumulatorCapacity = $newCapacity;
    }
}

/**
 * Applica l’effetto di tipo C (maintenance plan) al lifeCycle:
 * RL_final = floor(baseRL * (1 + sommaPercAttiveC)), clamp a >=1.
 * Ritorna il RL finale da assegnare a una risorsa comprata in questo turno.
 */
function applyActiveCToLifeCycle(int $turn, int $baseRL): int
{
    global $ownedResources;
    $sumC = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === 'C' && isResourceActive($turn, $res)) {
            $p = ((float)$res['effectPercent']) / 100.0;
            $sumC += $p; // può essere positivo (green) o negativo (non-green)
        }
    }
    $finalRL = (int) floor($baseRL * (1.0 + $sumC));
    if ($finalRL < 1) {
        $finalRL = 1;
    }
    return $finalRL;
}

/**
 * Funzione per acquistare una risorsa. Se l’acquisto riesce, aggiorna $capital e $ownedResources.
 */
function buyResource(int $rId, int $turn): bool
{
    global $resources, $capital, $ownedResources, $placedResources;

    $r = $resources[$rId];
    if ($capital < $r->activationCost) {
        return false;
    }
    // Paghiamo il costo
    $capital -= $r->activationCost;

    // Calcolo finale del lifeCycle considerando C
    $finalLife = applyActiveCToLifeCycle($turn, $r->lifeCycle);

    // Salviamo la risorsa acquistata
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
        'effectPercent'  => $r->percentage, // es. 75 -> 0.75, -10 -> -0.10
    ];

    // Per output
    $placedResources[$turn][] = $r->id;

    echo "Acquistata risorsa #{$r->id} al turno $turn\n";

    return true;
}

/**
 * Somma delle percentuali di tipo T (A, B, D) attive in questo turno.
 * es: 2 risorse B con +25% e -10% => sumB = 0.25 + (-0.10) = +0.15
 */
function getSumOfPerc(string $type, int $turn): float
{
    global $ownedResources;
    $sum = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === $type && isResourceActive($turn, $res)) {
            $sum += ((float)$res['effectPercent'] / 100.0);
        }
    }
    return $sum;
}

/**
 * Applica la formula newVal = floor(baseVal + baseVal * sumPerc),
 * clamp a 0 in caso di valore negativo.
 */
function applyAdditivePercent(int $baseVal, float $sumPerc): int
{
    $val = (int) floor($baseVal * (1.0 + $sumPerc));
    return max($val, 0);
}

/**
 * Ritorna quanti edifici totali alimentano le risorse attive, considerando l'effetto A globale.
 */
function getActivePoweredBuildings(int $turn): int
{
    global $ownedResources;

    // Calcoliamo sumPerc per A
    $sumA = getSumOfPerc('A', $turn); // somma totale di +/-

    $total = 0;
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        $ruBase = $res['baseBuildings'];
        // RU finale = floor(ruBase + ruBase*sumA), clamp >= 0
        $ruFinal = applyAdditivePercent($ruBase, $sumA);
        $total += $ruFinal;
    }
    return $total;
}

// ------------------------------------------------------------------------------------
// LOOP PRINCIPALE SUI TURNI
// ------------------------------------------------------------------------------------

$totalScore = 0;

for ($t = 0; $t < $turnsCount; $t++) {

    // Aggiorniamo gli accumulatori (in caso qualche E sia scaduta)
    updateAccumulatorCapacityAndStored($t);

    // 1) Calcoliamo i thresholds e TR finali, tenendo conto di B e D
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    $sumB = getSumOfPerc('B', $t); // influisce su minB, maxB
    $sumD = getSumOfPerc('D', $t); // influisce su TR

    // finalMinB, finalMaxB
    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);

    // finalTR
    $finalTR = applyAdditivePercent($baseTR, $sumD);

    // 2) Paghiamo i costi periodici di tutte le risorse vive
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            $maintenanceCost += $res['periodicCost'];
        }
    }
    // Se non abbiamo abbastanza capitale, (per semplicità) fermiamo tutto
    // In una strategia più raffinata si potrebbero vendere risorse, ma non è previsto.
    if ($capital < $maintenanceCost) {
        break;
    }
    // Aggiorniamo il capitale
    $capital -= $maintenanceCost;

    // 3) Calcoliamo edifici alimentati prima di eventuali acquisti
    $poweredByResources = getActivePoweredBuildings($t);

    // 4) Se non arriviamo a finalMinB, proviamo a comprare risorse in modo greedy
    if ($poweredByResources < $finalMinB) {
        // Ordiniamo le risorse per "activationCost / baseBuildings" asc, per comprare le più efficienti
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

        // Ripetiamo finché non raggiungiamo finalMinB o non riusciamo più a comprare
        while ($poweredByResources < $finalMinB) {
            $bought = false;
            foreach ($sortedIds as $rid) {
                if (buyResource($rid, $t)) {
                    // Ricalcoliamo la potenza dopo l’acquisto
                    $poweredByResources = getActivePoweredBuildings($t);
                    $bought = true;
                    if ($poweredByResources >= $finalMinB) {
                        break;
                    }
                }
            }
            if (!$bought) {
                break; // non possiamo più comprare
            }
        }
    }

    // Ricalcoliamo edifici alimentati dopo eventuali acquisti
    $poweredByResources = getActivePoweredBuildings($t);

    // 5) Gestione accumulatori E (surplus / deficit)
    if ($poweredByResources > $finalMaxB) {
        // Surplus da stoccare
        $surplus = $poweredByResources - $finalMaxB;
        $free    = $accumulatorCapacity - $accumulatorStored;
        $store   = min($surplus, $free);
        $accumulatorStored += $store;
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        // Copriamo con l'accumulatore
        $needed = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $needed) {
            // Copriamo interamente
            $accumulatorStored -= $needed;
            $poweredByResources = $finalMinB;
        } else {
            // Solo parziale
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // 6) Calcolo profitto
    $profit = 0;
    if ($poweredByResources >= $finalMinB) {
        // Sfruttiamo al max finalMaxB
        $actualPowered = min($poweredByResources, $finalMaxB);
        $profit = $actualPowered * $finalTR;
    }

    // Aggiorniamo capitale e totalScore
    $capital   += $profit;
    $totalScore += $profit;

    // FACOLTATIVO: debug
    // echo "TURNO $t: powered=$poweredByResources, profit=$profit, capital=$capital\n";
}

// ------------------------------------------------------------------------------------
// A questo punto, la simulazione è conclusa. Abbiamo $placedResources per l’output
// e $totalScore come valore complessivo del punteggio.
// Stampiamolo:
echo "TOTAL SCORE: $totalScore\n";
echo "CAPITAL FINALE: $capital\n";


/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), $totalScore);
