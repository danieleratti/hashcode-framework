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

// Array che memorizza tutte le risorse possedute, con dati sulla loro "età" per calcolare
// se sono ancora attive e per pagare i costi di manutenzione.
$ownedResources = []; // ciascun elemento: [
//   'id' => int,
//   'turnAcquired' => int,
//   'activationCost' => int,
//   'periodicCost' => int,
//   'activeTurns' => int,
//   'downtimeTurns' => int,
//   'lifeCycle' => int,
//   'buildingsCapacity' => int
// ]

// Array che per ogni turno tiene traccia delle risorse comprate in quel turno
$placedResources = [];

// Salviamo il budget iniziale in una variabile locale comoda
$capital = $initialCapital;

/**
 * Verifica se una risorsa (già comprata) è attiva in un determinato turno t.
 */
function isResourceActive(int $turn, array $resData): bool
{
    // Da quanti turni la risorsa esiste
    $age = $turn - $resData['turnAcquired'];
    // Se l'età è fuori dal range di vita totale, non è più utilizzabile
    if ($age < 0 || $age >= $resData['lifeCycle']) {
        return false;
    }
    // Calcoliamo la fase del ciclo in base a activeTurns + downtimeTurns
    $cycleLength = $resData['activeTurns'] + $resData['downtimeTurns'];
    $phaseInCycle = $age % $cycleLength;
    // Se siamo in una fase < activeTurns, la risorsa produce
    return ($phaseInCycle < $resData['activeTurns']);
}

/**
 * Ritorna quanti edifici totali vengono alimentati al turno t, sommando le risorse attive.
 */
function getPoweredBuildings(int $turn, array $ownedResources): int
{
    $sum = 0;
    foreach ($ownedResources as $res) {
        if (isResourceActive($turn, $res)) {
            $sum += $res['buildingsCapacity'];
        }
    }
    return $sum;
}

/**
 * Prova ad acquistare una risorsa specifica, aggiornando budget e strutture.
 * Restituisce true se l'acquisto è andato a buon fine, false altrimenti.
 */
function buyResource(int $rId, int $turn): bool
{
    global $resources, $ownedResources, $placedResources, $capital;

    $r = $resources[$rId];
    // Controlliamo se abbiamo abbastanza budget
    if ($capital < $r->activationCost) {
        return false;
    }
    // Paghiamo il costo di attivazione
    $capital -= $r->activationCost;

    // Registriamo la risorsa acquistata
    $ownedResources[] = [
        'id'               => $r->id,
        'turnAcquired'     => $turn,
        'activationCost'   => $r->activationCost,
        'periodicCost'     => $r->periodicCost,
        'activeTurns'      => $r->activeTurns,
        'downtimeTurns'    => $r->downtimeTurns,
        'lifeCycle'        => $r->lifeCycle,
        'buildingsCapacity'=> $r->buildingsCapacity,
    ];
    // Salviamo anche su placedResources per l'output
    $placedResources[$turn][] = $r->id;

    echo "Piazzo risorsa #{$r->id} al turno $turn\n";
    return true;
}

// -------------------------------------------------
// Iniziamo la simulazione dei turni
// -------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {

    // 1) Prima di tutto, paghiamo i costi di manutenzione delle risorse vive
    $maintenanceCost = 0;
    foreach ($ownedResources as $resData) {
        // Se la risorsa è ancora in vita (anche se in downtime), va pagata
        $age = $t - $resData['turnAcquired'];
        if ($age >= 0 && $age < $resData['lifeCycle']) {
            $maintenanceCost += $resData['periodicCost'];
        }
    }

    // Se non riusciamo a pagare la manutenzione, ci fermiamo (oppure potresti vendere risorse, ma qui non c'è)
    if ($capital < $maintenanceCost) {
        break;
    }

    // Aggiorniamo il capitale sottraendo i costi periodici
    $capital -= $maintenanceCost;

    // 2) Calcoliamo il numero di edifici già alimentati dalle risorse attive
    $powered = getPoweredBuildings($t, $ownedResources);

    // 3) Se non raggiungiamo il minimo, proviamo ad acquistare risorse
    $minB = $turns[$t]->minBuildings;
    $maxB = $turns[$t]->maxBuildings;
    $profitPerB = $turns[$t]->profitPerBuilding;

    // Strategia greedy: compra la risorsa "migliore" in base al rapporto
    // "capacità edifici / costo attivazione", finché non raggiungiamo il minimo (o finché abbiamo budget).
    // Nota: si potrebbe fare un ordinamento delle risorse e tentare acquisti multipli.
    if ($powered < $minB) {
        // Ordiniamo le risorse per "costoAttivazione / buildingsCapacity" crescente
        $sortedIds = array_keys($resources);
        usort($sortedIds, function($a, $b) {
            global $resources;
            // Evita divisione per zero
            $valA = ($resources[$a]->buildingsCapacity <= 0)
                ? PHP_INT_MAX
                : $resources[$a]->activationCost / $resources[$a]->buildingsCapacity;
            $valB = ($resources[$b]->buildingsCapacity <= 0)
                ? PHP_INT_MAX
                : $resources[$b]->activationCost / $resources[$b]->buildingsCapacity;
            return $valA <=> $valB; // sort asc
        });

        // Finché non soddisfiamo il minimo, proviamo ad acquistare risorse in ordine
        while ($powered < $minB) {
            $boughtAnything = false;
            foreach ($sortedIds as $rid) {
                if (buyResource($rid, $t)) {
                    // Dopo un acquisto, ricalcola la potenza
                    $powered = getPoweredBuildings($t, $ownedResources);
                    $boughtAnything = true;
                    if ($powered >= $minB) {
                        break;
                    }
                }
            }
            // Se non riusciamo più a comprare nulla, usciamo
            if (!$boughtAnything) {
                break;
            }
        }
    }

    // 4) Calcoliamo il profitto del turno
    if ($powered < $minB) {
        $profit = 0;
    } else {
        // Al massimo si guadagna su maxB edifici
        $profit = min($powered, $maxB) * $profitPerB;
    }

    // 5) Aggiorniamo il budget con il profitto
    $capital += $profit;

    // Qui potresti eventualmente salvare informazioni sullo score, ecc.
}

// Al termine, l'array $placedResources contiene le risorse comprate a ogni turno
// e la variabile $capital indica quanto budget rimane.

// Fine algoritmo


/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), "unknown");
