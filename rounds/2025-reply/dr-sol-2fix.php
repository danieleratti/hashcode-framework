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


// Array that tracks active resources with all their state information and effects
$ownedResources = [];

// Resources purchased each turn (for output)
$placedResources = [];

// Track accumulators separately for easier management
$accumulators = [];

// Current budget
$capital = $initialCapital;

// Total score tracker
$totalScore = 0;

/**
 * Apply special effects to calculate effective values for different parameters
 */
function applySpecialEffects($turn, $paramName, $baseValue) {
    global $resources, $ownedResources;

    $effectMultiplier = 1.0;

    // Find active resources with special effects that impact this parameter
    foreach ($ownedResources as $res) {
        if (!isResourceActive($turn, $res)) continue;

        // Get the original resource definition to check effect type
        $originalRes = $resources[$res['resourceId']];

        if ($paramName === 'buildingCapacity' && $originalRes->specialEffect === 'A') {
            // Smart Meter effect on building capacity
            $effectMultiplier += ($originalRes->percentage / 100);
        }
        else if ($paramName === 'thresholds' && $originalRes->specialEffect === 'B') {
            // Distribution Facility effect on thresholds
            $effectMultiplier += ($originalRes->percentage / 100);
        }
        else if ($paramName === 'profit' && $originalRes->specialEffect === 'D') {
            // Renewable Plant effect on profit
            $effectMultiplier += ($originalRes->percentage / 100);
        }
    }

    return floor($baseValue * $effectMultiplier);
}

/**
 * Check if a resource is active at a given turn
 */
function isResourceActive($turn, $resData) {
    $age = $turn - $resData['turnAcquired'];

    // Out of lifecycle range
    if ($age < 0 || $age >= $resData['lifeCycle']) {
        return false;
    }

    // Check if in active period of cycle
    $cycleLength = $resData['activeTurns'] + $resData['downtimeTurns'];
    $phaseInCycle = $age % $cycleLength;
    return ($phaseInCycle < $resData['activeTurns']);
}

/**
 * Calculate total powered buildings considering all effects
 */
function getPoweredBuildings($turn) {
    global $resources, $ownedResources;

    $sum = 0;
    foreach ($ownedResources as $res) {
        if (isResourceActive($turn, $res)) {
            // Apply Smart Meter (A) effect if needed
            $capacity = $res['buildingsCapacity'];
            $effectiveCapacity = applySpecialEffects($turn, 'buildingCapacity', $capacity);
            $sum += $effectiveCapacity;
        }
    }
    return $sum;
}

/**
 * Buy a resource with consideration of C-type effects
 */
function buyResource($rId, $turn) {
    global $resources, $ownedResources, $placedResources, $capital, $accumulators;

    $r = $resources[$rId];
    if ($capital < $r->activationCost) {
        return false;
    }

    // Pay activation cost
    $capital -= $r->activationCost;

    // Apply C-type effects (Maintenance Plan) to this resource's lifecycle
    $lifeCycle = $r->lifeCycle;
    foreach ($ownedResources as $existingRes) {
        if (isResourceActive($turn, $existingRes)) {
            $originalRes = $resources[$existingRes['resourceId']];
            if ($originalRes->specialEffect === 'C') {
                // Extend or reduce lifecycle based on percentage
                $modifier = $originalRes->percentage / 100;
                $lifeCycle = floor($lifeCycle * (1 + $modifier));
                // Ensure minimum life cycle of 1
                $lifeCycle = max(1, $lifeCycle);
            }
        }
    }

    // Create resource data structure
    $newResource = [
        'resourceId'       => $r->id,
        'turnAcquired'     => $turn,
        'activationCost'   => $r->activationCost,
        'periodicCost'     => $r->periodicCost,
        'activeTurns'      => $r->activeTurns,
        'downtimeTurns'    => $r->downtimeTurns,
        'lifeCycle'        => $lifeCycle, // Modified by C-type resources
        'buildingsCapacity'=> $r->buildingsCapacity,
        'specialEffect'    => $r->specialEffect
    ];

    $ownedResources[] = $newResource;

    // Initialize placedResources array for this turn if needed
    if (!isset($placedResources[$turn])) {
        $placedResources[$turn] = [];
    }
    $placedResources[$turn][] = $r->id;

    // If this is an accumulator (E), track it separately
    if ($r->specialEffect === 'E') {
        $accumulators[] = [
            'index' => count($ownedResources) - 1, // Reference to ownedResources
            'stored' => 0, // Current stored buildings
            'capacity' => $r->percentage // Max storage capacity
        ];
    }

    return true;
}

/**
 * Handle accumulator logic - store excess or use stored capacity
 */
function manageAccumulators($turn, $poweredBuildings, $minBuildings, $maxBuildings) {
    global $ownedResources, $accumulators;

    // If we're already meeting minimum requirements, nothing to do
    if ($poweredBuildings >= $minBuildings) {
        // Store excess if we're above maximum
        $excess = max(0, $poweredBuildings - $maxBuildings);
        if ($excess > 0) {
            // Try to store in active accumulators
            foreach ($accumulators as &$acc) {
                if (isResourceActive($turn, $ownedResources[$acc['index']])) {
                    $availableSpace = $acc['capacity'] - $acc['stored'];
                    $toStore = min($excess, $availableSpace);
                    $acc['stored'] += $toStore;
                    $excess -= $toStore;

                    if ($excess <= 0) break;
                }
            }
        }
        return $poweredBuildings;
    }

    // We're below minimum, try to use stored energy
    $deficit = $minBuildings - $poweredBuildings;
    $retrieved = 0;

    foreach ($accumulators as &$acc) {
        if (isResourceActive($turn, $ownedResources[$acc['index']]) && $acc['stored'] > 0) {
            $toRetrieve = min($deficit, $acc['stored']);
            $acc['stored'] -= $toRetrieve;
            $retrieved += $toRetrieve;
            $deficit -= $toRetrieve;

            if ($deficit <= 0) break;
        }
    }

    return $poweredBuildings + $retrieved;
}

/**
 * Estimate return on investment for purchasing a resource
 */
function calculateROI($resourceId, $turn, $lookAhead = 3) {
    global $resources, $turns, $turnsCount;

    $r = $resources[$resourceId];

    // Basic cost over lifecycle
    $totalCost = $r->activationCost + ($r->periodicCost * $r->lifeCycle);

    // Estimate revenue potential
    $potentialRevenue = 0;
    $activeTurnsLeft = $r->activeTurns; // How many active turns this resource has

    for ($t = $turn; $t < min($turn + $lookAhead, $turnsCount); $t++) {
        if ($activeTurnsLeft <= 0) break;

        // We're in an active turn for this resource
        $profitPerBuilding = $turns[$t]->profitPerBuilding;
        $potentialRevenue += min($r->buildingsCapacity, $turns[$t]->maxBuildings) * $profitPerBuilding;

        $activeTurnsLeft--;
    }

    // If cost is zero (shouldn't happen), return a high value
    if ($totalCost == 0) return 1000;

    return $potentialRevenue / $totalCost;
}

/**
 * Transfer stored energy between accumulators when one expires
 */
function transferAccumulatedEnergy($turn) {
    global $ownedResources, $accumulators;

    foreach ($accumulators as $i => &$acc) {
        $res = $ownedResources[$acc['index']];
        $age = $turn - $res['turnAcquired'];

        // If accumulator is at end of life and has stored energy
        if ($age == $res['lifeCycle'] - 1 && $acc['stored'] > 0) {
            // Try to transfer to other active accumulators
            foreach ($accumulators as &$targetAcc) {
                if ($i != array_search($targetAcc, $accumulators) &&
                    isResourceActive($turn, $ownedResources[$targetAcc['index']])) {

                    $spaceLeft = $targetAcc['capacity'] - $targetAcc['stored'];
                    $toTransfer = min($acc['stored'], $spaceLeft);

                    $targetAcc['stored'] += $toTransfer;
                    $acc['stored'] -= $toTransfer;

                    if ($acc['stored'] <= 0) break;
                }
            }
        }
    }
}

// Main game loop
for ($t = 0; $t < $turnsCount; $t++) {
    // 1. Pay maintenance costs for active resources
    $maintenanceCost = 0;
    foreach ($ownedResources as $res) {
        $age = $t - $res['turnAcquired'];
        if ($age >= 0 && $age < $res['lifeCycle']) {
            $maintenanceCost += $res['periodicCost'];
        }
    }
    $capital -= $maintenanceCost;

    // 2. Get current powered buildings
    $poweredBuildings = getPoweredBuildings($t);

    // 3. Apply B-type effects to thresholds
    $minBuildings = applySpecialEffects($t, 'thresholds', $turns[$t]->minBuildings);
    $maxBuildings = applySpecialEffects($t, 'thresholds', $turns[$t]->maxBuildings);

    // 4. Strategic resource purchasing
    $shouldBuyMore = true;
    $resourcesByROI = [];

    foreach ($resources as $rid => $r) {
        $roi = calculateROI($rid, $t);
        if ($roi > 0 && $r->activationCost <= $capital) {
            $resourcesByROI[$rid] = $roi;
        }
    }

    // Sort by ROI (highest first)
    arsort($resourcesByROI);

    // Buy resources until we either meet our needs or run out of good options
    while ($shouldBuyMore && !empty($resourcesByROI)) {
        // Get best resource by ROI
        $bestResourceId = key($resourcesByROI);
        unset($resourcesByROI[$bestResourceId]);

        // Buy the resource
        if (buyResource($bestResourceId, $t)) {
            // Recalculate powered buildings
            $poweredBuildings = getPoweredBuildings($t);

            // If we've exceeded minimum by a comfortable margin, stop buying
            // unless we have a lot of capital
            if ($poweredBuildings >= $minBuildings * 1.2 && $capital < $minBuildings * 5) {
                $shouldBuyMore = false;
            }

            // If we're getting low on capital, be more conservative
            if ($capital < 10) {
                $shouldBuyMore = false;
            }
        }
    }

    // 5. Manage accumulators (E-type resources)
    $effectivePowered = manageAccumulators($t, $poweredBuildings, $minBuildings, $maxBuildings);

    // 6. Calculate profit for this turn
    $profitPerBuilding = applySpecialEffects($t, 'profit', $turns[$t]->profitPerBuilding);

    if ($effectivePowered >= $minBuildings) {
        $turnProfit = min($effectivePowered, $maxBuildings) * $profitPerBuilding;
        $capital += $turnProfit;
        $totalScore += $turnProfit;
    }

    // 7. Handle energy transfer for expiring accumulators
    transferAccumulatedEnergy($t);
}




/** FINE ALGORITMO */

$fileManager->outputV2(getOutput(), 'Unknown');
