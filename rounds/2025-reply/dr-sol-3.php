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

// Contiene i dati estesi delle risorse che possediamo (chiave = indice interno).
// Ogni elemento avrà:
//   'id'             => RI    (ID risorsa, come da input)
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

// Mappatura per output: in placedResources[t] salviamo tutte le risorse acquistate al turno t
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

    // Ricalcoliamo la capacity totale basandoci SOLO sugli E vivi e attivi (cioè non usciti dal RL)
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
 * Ritorna true se una risorsa è ancora “in vita”, cioè se la differenza
 * (turn - turnoAcquisizione) < lifeCycle
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
    // Ricorda che C può essere green (RE>0) o non-green (RE<0).
    $sumPerc = 0.0;
    foreach ($ownedResources as $res) {
        if ($res['specialType'] === 'C' && isResourceActive($turn, $res)) {
            // RE è un intero: es 75 => 0.75
            $p = ((float)$res['effectPercent']) / 100.0;
            $sumPerc += $p;
        }
    }

    // RL finale = floor( baseRL + baseRL*sumPerc ) = floor( baseRL*(1+sumPerc) )
    $newRL = (int) floor($baseRL * (1.0 + $sumPerc));
    // Non scendiamo mai sotto 1
    if ($newRL < 1) {
        $newRL = 1;
    }
    return $newRL;
}

/**
 * Funzione di acquisto risorsa singola.
 * Se l'acquisto va a buon fine, aggiorna budget e possedimenti.
 */
function buyResource(int $rId, int $turn): bool
{
    global $resources, $capital, $ownedResources, $placedResources;

    $r = $resources[$rId];
    $cost = $r->activationCost;
    if ($capital < $cost) {
        return false;
    }
    // Paghiamo
    $capital -= $cost;

    // Calcoliamo la lifeCycle finale considerando gli effetti C attivi
    $finalLife = applyActiveCToLifeCycle($turn, $r->lifeCycle);

    // Aggiungiamo la risorsa all'inventario
    $ownedResources[] = [
        'id'             => $r->id,
        'turnAcquired'   => $turn,
        'activationCost' => $r->activationCost,
        'periodicCost'   => $r->periodicCost,
        'baseActive'     => $r->activeTurns,
        'baseDowntime'   => $r->downtimeTurns,
        // RL con eventuale modifica di C:
        'lifeCycle'      => $finalLife,
        'baseBuildings'  => $r->buildingsCapacity,
        'specialType'    => $r->specialEffect,
        'effectPercent'  => $r->percentage, // potrebbe essere null
    ];

    // Salviamo anche in placedResources per output
    $placedResources[$turn][] = $r->id;

    echo "Acquistata risorsa #{$r->id} al turno $turn\n";
    return true;
}

// ------------------------------------------------------------------------------------
// CALCOLO DEI PARAMETRI MODIFICATI DA (A, B, D) AL TURN T
// ------------------------------------------------------------------------------------

/**
 * Ritorna il fattore moltiplicativo finale (>=0) dovuto a tutte le risorse di tipo $type (A,B o D),
 * sommandole come da specifica (base + 0.XX * base per ognuna).
 * Esempio: se abbiamo 2 risorse A con +75% e +50%, e 1 con -20%, allora
 * sumPerc = (0.75 + 0.50 - 0.20) = 1.05 => fattore = (1 + 1.05) = 2.05 => RU_new = floor(2.05 * RU_base).
 * In realtà la formula della traccia dice: RU_new = floor(RU + RU*0.75 + RU*0.50 + RU*(-0.20))
 * che è uguale a RU * (1 + sumPerc).
 *
 * Per B e D la logica è la stessa, solo che si applica a (minB, maxB) o (TR).
 *
 * Attenzione: se la somma delle percentuali è negativa, potremmo ridurre RU, TR o i threshold
 * fino a 0 (non sotto).
 */
function getSumOfPerc(string $type, int $turn): float
{
    global $ownedResources;
    $sum = 0.0;
    foreach ($ownedResources as $res) {
        // Se la risorsa non è in vita o non è attiva, skip
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        if ($res['specialType'] === $type) {
            // effettoPercent => es 75 => +0.75 se green, -0.75 se non-green
            $p = ((float)$res['effectPercent']) / 100.0;
            $sum += $p;
        }
    }
    return $sum;
}

/**
 * Applica la formula: newVal = floor( baseVal + baseVal * sumPerc ), clamp a [0, ∞).
 */
function applyAdditivePercent(int $baseVal, float $sumPerc): int
{
    $res = (int) floor($baseVal * (1.0 + $sumPerc));
    return max($res, 0); // non scendiamo sotto 0
}

// ------------------------------------------------------------------------------------
// CALCOLO EDIFICI ALIMENTATI (RU), CON EFFETTO A
// ------------------------------------------------------------------------------------
function getActivePoweredBuildings(int $turn): int
{
    global $ownedResources;
    // 1) Calcoliamo sumPerc per A
    $sumA = getSumOfPerc('A', $turn); // somma degli effetti +/-

    $total = 0;
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) {
            continue;
        }
        // RU "base" della risorsa
        $ruBase = $res['baseBuildings'];
        // RU finale = floor( ruBase + ruBase * sumA ) clamp >=0
        $ruFinal = applyAdditivePercent($ruBase, $sumA);
        $total += $ruFinal;
    }
    return $total;
}

// ------------------------------------------------------------------------------------
// FLUSSO PRINCIPALE: CICLO SUI TURNI
// ------------------------------------------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {

    // Aggiorniamo la capacità degli accumulatori in caso alcune E scadano
    updateAccumulatorCapacityAndStored($t);

    // Calcoliamo i thresholds e TR base
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    // Somma percentuali B e D
    $sumB = getSumOfPerc('B', $t); // influisce su (minB, maxB)
    $sumD = getSumOfPerc('D', $t); // influisce su TR

    // minB e maxB finali
    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);

    // TR finale
    $finalTR   = applyAdditivePercent($baseTR, $sumD);

    // 1) PAGAMENTO COSTI PERIODICI DI TUTTE LE RISORSE VIVE
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            // Anche se è in downtime, si paga
            $maintenanceCost += $res['periodicCost'];
        }
    }

    // Se non abbiamo abbastanza capitale per pagare la manutenzione, (strategia minimale) usciamo
    // In un algoritmo più complesso potresti vendere/disattivare risorse, ma qui non è previsto.
    if ($capital < $maintenanceCost) {
        break;
    }
    $capital -= $maintenanceCost;

    // 2) CALCOLO EDIFICI ALIMENTATI DALLE RISORSE ATTIVE
    $poweredByResources = getActivePoweredBuildings($t);

    // 3) STRATEGIA DI ACQUISTO: se non raggiungiamo finalMinB, proviamo a comprare
    //    in modo greedy basato su "capacità edifici/costo attivazione".
    if ($poweredByResources < $finalMinB) {
        // Ordiniamo le risorse in base a "activationCost / baseBuildings" (crescente)
        // Così compriamo prima quelle più "efficienti"
        $sortedIds = array_keys($resources);
        usort($sortedIds, function($a, $b) {
            global $resources;
            $ra = $resources[$a];
            $rb = $resources[$b];
            // Evitiamo div0
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

        while ($poweredByResources < $finalMinB) {
            $boughtSomething = false;
            foreach ($sortedIds as $rid) {
                if (buyResource($rid, $t)) {
                    // Ricalcoliamo poweredByResources dopo l'acquisto
                    $poweredByResources = getActivePoweredBuildings($t);
                    $boughtSomething = true;
                    if ($poweredByResources >= $finalMinB) {
                        break;
                    }
                }
            }
            // Se non riusciamo a comprare nulla, smettiamo
            if (!$boughtSomething) {
                break;
            }
        }
    }

    // 4) Dopo gli acquisti, ricalcoliamo (di nuovo) gli edifici alimentati
    $poweredByResources = getActivePoweredBuildings($t);

    // 5) Gestione accumulatori E:
    //    - Se poweredByResources > finalMaxB, immagazziniamo l'eccesso (fino a capacity).
    //    - Se poweredByResources < finalMinB, cerchiamo di colmare dal serbatoio.
    // Attenzione a ordini e limiti.
    if ($poweredByResources > $finalMaxB) {
        // Surplus da immagazzinare
        $surplus = $poweredByResources - $finalMaxB;
        // Capacità libera?
        $freeSpace = $accumulatorCapacity - $accumulatorStored;
        $toStore   = min($surplus, $freeSpace);
        $accumulatorStored += $toStore;
        // I powered effettivi non vanno oltre finalMaxB (non serve alimentare di più)
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        // Dobbiamo coprire la differenza con l'accumulatore
        $need = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $need) {
            // Possiamo prendere tutto
            $accumulatorStored -= $need;
            $poweredByResources = $finalMinB;
        } else {
            // Ne abbiamo solo una parte
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // Adesso poweredByResources è compreso tra finalMinB e finalMaxB (se possibile).
    // Se comunque non è >= finalMinB, il profitto è 0
    if ($poweredByResources < $finalMinB) {
        $profit = 0;
    } else {
        // profit = min(poweredByResources, finalMaxB) * finalTR
        // (ma in questa fase poweredByResources è già <= finalMaxB)
        $profit = $poweredByResources * $finalTR;
    }

    // 6) Aggiorniamo il capitale
    $capital += $profit;

    // echo "TURNO $t -> powered=$poweredByResources, profit=$profit, capital=$capital\n";
}

// Al termine, $placedResources ha la lista delle risorse acquistate per ogni turno
// e $capital indica il budget rimanente.
// L'output verrà prodotto da getOutput() (già presente nello snippet iniziale).






/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), "unknown");
