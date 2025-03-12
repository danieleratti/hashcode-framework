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
// -- We will accumulate all turn profits here, as per PDF's scoring rules:
$SCORE = 0;

Log::out("Run started...");


/** QUI DEVI SCRIVERE L'ALGORITMO
 * */

// ------------------------------------------------------------------------------------
// STRUTTURE E VARIABILI GLOBALI DI SUPPORTO
// ------------------------------------------------------------------------------------

// Contiene i dati estesi delle risorse che possediamo (chiave = indice interno).
// Ogni elemento avrà:
//   'id'             => RI
//   'turnAcquired'   => t in cui è stata acquistata
//   'activationCost' => RA
//   'periodicCost'   => RP
//   'baseActive'     => RW  (numero di turni consecutivi di attività "base")
//   'baseDowntime'   => RM
//   'lifeCycle'      => RL  (attenzione: può essere modificato da C subito all'acquisto)
//   'baseBuildings'  => RU  (valore "base", su cui si applicano A in runtime)
//   'specialType'    => RT  (A,B,C,D,E o X)
//   'effectPercent'  => RE  (se esiste, altrimenti null)
//
$ownedResources = [];

// Mappatura per output: in placedResources[t] salviamo tutte le risorse acquistate per ogni turno
$placedResources = [];

// Budget disponibile (modificato a ogni turno)
$capital = $initialCapital;

// ------------------------------------------------------------------------------------
// GESTIONE ACCUMULATORI (E)
// ------------------------------------------------------------------------------------
//
// Avendo più risorse di tipo E, si sommano le loro capacità RE in un unico grande contenitore.
// - $accumulatorCapacity  = somma delle capacità di TUTTE le E attive
// - $accumulatorStored    = quanti "edifici virtuali" abbiamo già immagazzinato
// Per gestire fine vita di un accumulatore, controlliamo se la risorsa E scade in questo turno
// e riduciamo la capacity di conseguenza, rimuovendo eventuale stored in eccesso.
//
$accumulatorCapacity = 0;
$accumulatorStored   = 0;

/**
 * Aggiorna la capacità del serbatoio globale di accumulatori (E).
 * Elimina la parte di capacità che viene persa alla fine di vita di alcuni E,
 * e ridistribuisce se possibile lo storage in eccesso.
 */
function updateAccumulatorCapacityAndStored(int $turn)
{
    global $ownedResources, $accumulatorCapacity, $accumulatorStored;

    // Ricalcoliamo la capacity totale basandoci SOLO sugli E vivi e attivi
    $newCapacity  = 0;
    foreach ($ownedResources as $resData) {
        if ($resData['specialType'] !== 'E') {
            continue;
        }
        // Controlliamo se è ancora in vita
        $age = $turn - $resData['turnAcquired'];
        if ($age >= 0 && $age < $resData['lifeCycle']) {
            // Aggiungiamo la capacità RE
            $cap = (int)$resData['effectPercent'];
            if ($cap > 0) {
                $newCapacity += $cap;
            }
        }
    }

    // Se la nuova capacity è minore di quella attuale, dobbiamo scartare parte dello stored
    if ($newCapacity < $accumulatorCapacity) {
        $reduce = $accumulatorCapacity - $newCapacity;
        // riduciamo prima la capacity
        $accumulatorCapacity = $newCapacity;
        // se stored > capacity, tagliamo l'eccesso
        if ($accumulatorStored > $accumulatorCapacity) {
            $accumulatorStored = $accumulatorCapacity;
        }
    } else {
        // Altrimenti aumentiamo la capacity al nuovo valore
        $accumulatorCapacity = $newCapacity;
    }
}

/**
 * Ritorna true se una risorsa è ancora “in vita”
 */
function isResourceInLife(int $turn, array $res): bool
{
    $age = $turn - $res['turnAcquired'];
    return ($age >= 0 && $age < $res['lifeCycle']);
}

/**
 * Ritorna true se una risorsa è attiva in un determinato turno (cioè produce RU o i suoi effetti A/B/D/E)
 */
function isResourceActive(int $turn, array $res): bool
{
    if (!isResourceInLife($turn, $res)) {
        return false;
    }
    $age  = $turn - $res['turnAcquired'];
    $cLen = $res['baseActive'] + $res['baseDowntime'];
    $phase = $age % $cLen;
    // se phase < baseActive => la risorsa è "on"
    return ($phase < $res['baseActive']);
}

// ------------------------------------------------------------------------------------
// ACQUISTO RISORSE
// ------------------------------------------------------------------------------------

/**
 * Applica l'effetto di tutte le C attive al momento dell'acquisto: modifica la lifeCycle.
 * - Sommiamo i *percentuali* di tipo C e applichiamo la formula baseRL * (1 + sommaPerc).
 * - Se la percentuale totale è negativa, riduciamo RL (ma >=1).
 */
function applyActiveCToLifeCycle(int $turn, int $baseRL): int
{
    global $ownedResources;
    // Calcoliamo la somma delle percentuali di TUTTE le C attive
    $sumPerc = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === 'C' && isResourceActive($turn, $res)) {
            $p = ((float)$res['effectPercent']) / 100.0;
            $sumPerc += $p;
        }
    }

    $newRL = (int) floor($baseRL * (1.0 + $sumPerc));
    if ($newRL < 1) {
        $newRL = 1;
    }
    return $newRL;
}

/**
 * Funzione di acquisto risorsa singola.
 */
$tCounts = [];
function buyResource(int $rId, int $turn): bool
{
    global $resources, $capital, $ownedResources, $placedResources, $tCounts;

    $tCounts[$turn]++;
    if($tCounts[$turn] > 50)
        return false; // DI SICUREZZA

    $r = $resources[$rId];
    $cost = $r->activationCost;
    if ($capital < $cost) {
        return false;
    }
    // Paghiamo
    $capital -= $cost;

    // Calcoliamo la lifeCycle finale con le C attive
    $finalLife = applyActiveCToLifeCycle($turn, $r->lifeCycle);

    // Aggiungiamo la risorsa all'inventario
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
        'effectPercent'  => $r->percentage, // potrebbe essere null
    ];

    // Salviamo anche in placedResources per output
    $placedResources[$turn][] = $r->id;

    #echo "Acquistata risorsa #{$r->id} al turno $turn\n";
    return true;
}

// ------------------------------------------------------------------------------------
// CALCOLO DEI PARAMETRI MODIFICATI DA (A, B, D) AL TURN T
// ------------------------------------------------------------------------------------

function getSumOfPerc(string $type, int $turn): float
{
    global $ownedResources;
    $sum = 0.0;
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        if ($res['specialType'] === $type) {
            $p = ((float)$res['effectPercent']) / 100.0;
            $sum += $p;
        }
    }
    return $sum;
}

function applyAdditivePercent(int $baseVal, float $sumPerc): int
{
    $res = (int) floor($baseVal * (1.0 + $sumPerc));
    return max($res, 0);
}

// ------------------------------------------------------------------------------------
// CALCOLO EDIFICI ALIMENTATI (RU), CON EFFETTO A
// ------------------------------------------------------------------------------------
function getActivePoweredBuildings(int $turn): int
{
    global $ownedResources;
    $sumA = getSumOfPerc('A', $turn);

    $total = 0;
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        $ruBase = $res['baseBuildings'];
        $ruFinal = applyAdditivePercent($ruBase, $sumA);
        $total += $ruFinal;
    }
    return $total;
}

// ------------------------------------------------------------------------------------
// FLUSSO PRINCIPALE: CICLO SUI TURNI
// ------------------------------------------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {

    // 1) Aggiorniamo accumulatori
    updateAccumulatorCapacityAndStored($t);

    // 2) Calcoliamo minB/maxB/TR con gli effetti B e D
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    $sumB = getSumOfPerc('B', $t);
    $sumD = getSumOfPerc('D', $t);

    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);
    $finalTR   = applyAdditivePercent($baseTR, $sumD);

    // 3) Periodic costs e eventuale fine
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            $maintenanceCost += $res['periodicCost'];
        }
    }
    if ($capital < $maintenanceCost) {
        // se non riusciamo a pagare la manutenzione, interrompiamo qui
        echo "Interrompo a $t / $turnsCount";
        break;
    }
    // paga la manutenzione
    $capital -= $maintenanceCost;

    // 4) Acquisti (greedy se non raggiungiamo finalMinB)
    $poweredByResources = getActivePoweredBuildings($t);
    if ($poweredByResources < $finalMinB) {
        // Ordiniamo risorse in base a efficienza
        $sortedIds = array_keys($resources);
        usort($sortedIds, function($a, $b) {
            global $resources;
            $ra = $resources[$a];
            $rb = $resources[$b];
            $valA = ($ra->buildingsCapacity > 0)
                ? $ra->activationCost / pow($ra->buildingsCapacity, 1.0)
                : PHP_INT_MAX;
            $valB = ($rb->buildingsCapacity > 0)
                ? $rb->activationCost / pow($rb->buildingsCapacity, 1.0)
                : PHP_INT_MAX;

            return $valA <=> $valB;
        });

        if($capital > 1000) {
            $addedIds = [];

            if($capital < 1000000) {
                for ($i = 0; $i < min(12, round($capital / 180)); $i++) {
                    $addedIds[] = 37;
                }
                for ($i = 0; $i < min(12, round($capital / 550)); $i++) {
                    $addedIds[] = 27;
                }
                for ($i = 0; $i < min(26, round($capital / 400)); $i++) {
                    $addedIds[] = 20;
                }
            } else {
                for ($i = 0; $i < min(50, round($capital / 180)); $i++) {
                    $addedIds[] = 37;
                    $addedIds[] = 37;
                    $addedIds[] = 20;
                }
            }
            /*for($i=0;$i<max(20, round($capital/500));$i++) {
                $addedIds[] = 20;
            }*/

            $sortedIds = [
                ...$addedIds,
                ...$sortedIds,
            ];
        }

        $sortedIds = array_slice($sortedIds, 0, 50);

        while ($poweredByResources < $finalMinB) {
            $boughtSomething = false;
            foreach ($sortedIds as $rid) {
                if (buyResource($rid, $t)) {
                    $poweredByResources = getActivePoweredBuildings($t);
                    $boughtSomething = true;
                    $coeff = 1.5;
                    /*if($capital > 1000000)
                        $coeff = 2.0;*/
                    if ($poweredByResources >= $finalMaxB * $coeff) {
                        break;
                    }
                }
            }
            if (!$boughtSomething) {
                break;
            }
        }
    }

    // 5) Ricalcolo powered dopo acquisti
    $poweredByResources = getActivePoweredBuildings($t);

    // 6) Gestione accumulatore E
    if ($poweredByResources > $finalMaxB) {
        $surplus = $poweredByResources - $finalMaxB;
        echo "SURPLUS: $surplus\n";
        $freeSpace = $accumulatorCapacity - $accumulatorStored;
        $toStore   = min($surplus, $freeSpace);
        $accumulatorStored += $toStore;
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        echo "KO\n";
        $need = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $need) {
            $accumulatorStored -= $need;
            $poweredByResources = $finalMinB;
        } else {
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // 7) Calcolo profit e aggiorno budget
    $profit = 0;
    if ($poweredByResources >= $finalMinB) {
        $profit = $poweredByResources * $finalTR; // <= finalMaxB * finalTR
    }

    // Aggiorna capitale e ACCUMULA SCORE
    $capital += $profit;
    $SCORE += $profit; // <--- Add this to accumulate total profit

    // echo "TURNO $t -> powered=$poweredByResources, profit=$profit, capital=$capital\n";
    echo "€ $capital ($t / $turnsCount)\n";
}

// ------------------------------------------------------------------------------------
// OUTPUT: restituisci la soluzione e lo score finale
// ------------------------------------------------------------------------------------
echo "SCORE: $SCORE";
$fileManager->outputV2(getOutput(), $SCORE);
