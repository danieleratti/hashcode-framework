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
    if($placedResources)
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

/*  VECCHIO CODICE SOPRA */




// ------------------------------------------------------------------------------------
// STRATEGIA DI ACQUISTO MIGLIORATA
// ------------------------------------------------------------------------------------

// Modificare il calcolo del valore della risorsa per considerare più fattori
function calculateResourceValue($resource, $turn) {
    global $turns, $ownedResources;

    $r = $resource;
    $remainingTurns = $turnsCount - $turn;

    // Calcola turni attivi rimanenti (approssimativo)
    $activeTurnsRemaining = min($r->lifeCycle, $remainingTurns);
    $activeRatio = $r->activeTurns / ($r->activeTurns + $r->downtimeTurns);
    $estimatedActiveTurns = $activeTurnsRemaining * $activeRatio;

    // Valore base: edifici alimentati per turno attivo
    $baseValue = $r->buildingsCapacity;

    // Costo totale stimato (attivazione + manutenzione per vita utile)
    $totalCost = $r->activationCost + ($r->periodicCost * $activeTurnsRemaining);

    // Bonus per effetti speciali
    $specialBonus = 1.0;

    // Valorizziamo maggiormente gli effetti speciali positivi
    if ($r->specialEffect == 'A' && $r->percentage > 0) {
        // Smart Meter aumenta capacità - molto prezioso
        $specialBonus *= (1 + ($r->percentage / 50));
    }
    else if ($r->specialEffect == 'B' && $r->percentage > 0) {
        // Distribution Facility aumenta soglie - utile
        $specialBonus *= (1 + ($r->percentage / 75));
    }
    else if ($r->specialEffect == 'C' && $r->percentage > 0) {
        // Maintenance Plan estende vita - più valore se comprato presto
        if($turnsCount != 0) {
            $specialBonus *= (1 + ($r->percentage / 100) * (1 - $turn / $turnsCount));
        }
    }
    else if ($r->specialEffect == 'D' && $r->percentage > 0) {
        // Renewable Plant aumenta profitto - direttamente proporzionale
        $specialBonus *= (1 + ($r->percentage / 40));
    }
    else if ($r->specialEffect == 'E') {
        // Accumulator - più valore se fluttuazioni di richiesta
        $volatility = calculateRequirementVolatility($turn);
        $specialBonus *= (1 + $volatility * ($r->percentage / 50));
    }

    // Penalizza effetti negativi
    if (in_array($r->specialEffect, ['A', 'B', 'C', 'D']) && $r->percentage < 0) {
        $specialBonus *= (1 - abs($r->percentage) / 200);
    }

    // Formula finale: (valore * bonus / costo)
    return ($baseValue * $estimatedActiveTurns * $specialBonus) / max(1, $totalCost);
}

// Calcola volatilità delle richieste future (per valutare accumulatori)
function calculateRequirementVolatility($startTurn) {
    global $turns, $turnsCount;

    if ($startTurn >= $turnsCount - 3) return 0;

    $requirements = [];
    for ($t = $startTurn; $t < min($startTurn + 5, $turnsCount); $t++) {
        $requirements[] = $turns[$t]->minBuildings;
    }

    // Calcola deviazione standard normalizzata
    $avg = array_sum($requirements) / count($requirements);
    $variance = 0;
    foreach ($requirements as $req) {
        $variance += pow($req - $avg, 2);
    }
    $stddev = sqrt($variance / count($requirements));

    return $stddev / max(1, $avg); // Volatilità normalizzata
}

// Funzione principale per decidere gli acquisti
function strategicPurchase($turn) {
    global $resources, $capital, $ownedResources, $turns, $turnsCount;

    // Calcola il valore attuale della produzione
    $currentPower = getActivePoweredBuildings($turn);
    $finalMinB = applyAdditivePercent($turns[$turn]->minBuildings, getSumOfPerc('B', $turn));
    $finalMaxB = applyAdditivePercent($turns[$turn]->maxBuildings, getSumOfPerc('B', $turn));

    // Calcola capacità ideale target (più vicina a max che min, se possibile)
    $idealTarget = $finalMinB + ($finalMaxB - $finalMinB) * 0.8;

    // Proiezione risorse future (quante spariranno nei prossimi turni)
    $projectedLoss = 0;
    foreach ($ownedResources as $res) {
        $remainingLife = $res['lifeCycle'] - ($turn - $res['turnAcquired']);
        if ($remainingLife <= 3 && $remainingLife > 0 && isResourceActive($turn, $res)) {
            $projectedLoss += $res['baseBuildings'];
        }
    }

    // Decidi quanto capitale investire in questo turno
    $remainingTurns = $turnsCount - $turn;
    $investmentRatio = 1.0;

    // All'inizio investiamo di più, verso la fine meno
    if ($remainingTurns > $turnsCount / 2) {
        $investmentRatio = 0.8; // Investimento aggressivo all'inizio
    } else if ($remainingTurns > $turnsCount / 4) {
        $investmentRatio = 0.6; // Medio investimento
    } else {
        $investmentRatio = 0.4; // Minore investimento verso la fine
    }

    // Budget per questo turno
    $turnBudget = $capital * $investmentRatio;

    // Se siamo sotto il minimo o prevediamo di esserlo presto, più budget
    if ($currentPower < $finalMinB || $currentPower - $projectedLoss < $finalMinB) {
        $turnBudget = $capital * 0.9; // Quasi tutto il budget se rischiamo di non soddisfare minimo
    }

    // Se siamo già vicini alla fine, spendiamo tutto
    if ($remainingTurns <= 2) {
        $turnBudget = $capital; // Tutto il capitale rimanente
    }

    // Ordina risorse per valore
    $sortedResources = [];
    foreach ($resources as $id => $r) {
        $sortedResources[$id] = calculateResourceValue($r, $turn);
    }
    arsort($sortedResources); // Ordina per valore decrescente

    // Acquista risorse fino a esaurimento budget o target raggiunto
    $spent = 0;
    $boughtCount = 0;

    foreach ($sortedResources as $id => $value) {
        $r = $resources[$id];

        // Controlla se possiamo permetterci la risorsa
        if ($r->activationCost > $turnBudget - $spent) {
            continue;
        }

        // Verifica se conviene acquistare questa risorsa
        $shouldBuy = false;

        // Caso 1: Siamo sotto il target o prevediamo di esserlo
        if ($currentPower < $idealTarget || $currentPower - $projectedLoss < $idealTarget) {
            $shouldBuy = true;
        }
        // Caso 2: La risorsa ha effetti speciali molto validi
        else if (in_array($r->specialEffect, ['A', 'C', 'D', 'E']) && $r->percentage > 50) {
            $shouldBuy = true;
        }
        // Caso 3: Ultimi turni, spendiamo tutto se ha un buon rapporto valore/costo
        else if ($remainingTurns <= 3 && $value > 0.5) {
            $shouldBuy = true;
        }

        if ($shouldBuy) {
            // Acquista risorsa
            if (buyResource($id, $turn)) {
                $spent += $r->activationCost;
                $currentPower = getActivePoweredBuildings($turn); // Ricalcola potenza
                $boughtCount++;

                // Se abbiamo raggiunto il target e speso abbastanza, termina
                if ($currentPower >= $idealTarget && $spent >= $turnBudget * 0.7) {
                    break;
                }

                // Limita il numero di acquisti per turno
                if ($boughtCount >= 5) {
                    break;
                }
            }
        }
    }

    return $boughtCount > 0;
}

// ------------------------------------------------------------------------------------
// OTTIMIZZAZIONE FLUSSO PRINCIPALE
// ------------------------------------------------------------------------------------

for ($t = 0; $t < $turnsCount; $t++) {
    // Aggiorna accumulatori
    updateAccumulatorCapacityAndStored($t);

    // Calcola parametri con effetti speciali
    $baseMinB = $turns[$t]->minBuildings;
    $baseMaxB = $turns[$t]->maxBuildings;
    $baseTR   = $turns[$t]->profitPerBuilding;

    $sumB = getSumOfPerc('B', $t);
    $sumD = getSumOfPerc('D', $t);

    $finalMinB = applyAdditivePercent($baseMinB, $sumB);
    $finalMaxB = applyAdditivePercent($baseMaxB, $sumB);
    $finalTR   = applyAdditivePercent($baseTR, $sumD);

    // Pagamento costi di manutenzione
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        if (isResourceInLife($t, $res)) {
            $maintenanceCost += $res['periodicCost'];
        }
    }

    if ($capital < $maintenanceCost) {
        break; // Non abbiamo fondi per la manutenzione
    }
    $capital -= $maintenanceCost;

    // ACQUISTO STRATEGICO
    strategicPurchase($t);

    // Ricalcolo edifici alimentati dopo acquisti
    $poweredByResources = getActivePoweredBuildings($t);

    // Gestione accumulatori ottimizzata
    if ($poweredByResources > $finalMaxB) {
        // Surplus da immagazzinare
        $surplus = $poweredByResources - $finalMaxB;
        $freeSpace = $accumulatorCapacity - $accumulatorStored;
        $toStore = min($surplus, $freeSpace);
        $accumulatorStored += $toStore;
        $poweredByResources = $finalMaxB;
    } elseif ($poweredByResources < $finalMinB) {
        // Usa accumulatore solo se necessario per raggiungere minimo
        $need = $finalMinB - $poweredByResources;
        if ($accumulatorStored >= $need) {
            $accumulatorStored -= $need;
            $poweredByResources = $finalMinB;
        } else {
            $poweredByResources += $accumulatorStored;
            $accumulatorStored = 0;
        }
    }

    // Calcolo profitto
    if ($poweredByResources < $finalMinB) {
        $profit = 0;
    } else {
        $profit = min($poweredByResources, $finalMaxB) * $finalTR;
    }

    // Aggiornamento capitale
    $capital += $profit;
}




/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), 'Unknown');
