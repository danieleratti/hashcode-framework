<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'd';

include 'dic-reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$totalScore = 0;

for ($i = 0; $i < $steps; $i++) {
    $freeVeichles = $VEHICLES->filter(function ($v) {
        /** @var Vehicle $v */
        return $v->freeAt == 0;
    });
    if($totalScore == 2366708) {
        $foo = 'ciao';
    }

    if ($i == 0) {
        $initialRides = [];
        /** @var Vehicle $v */
        $v = $VEHICLES->first();
        foreach ($RIDES as $r) {
            //$initialRides[$r->id] = $v->distanceFromStartingRide($r);
            $initialRides[] = [
                'id' => $r->id,
                'distance' => $v->distanceFromStartingRide($r)
            ];
        }
        //arsort($initialRides);
        $initialRides = collect($initialRides)->keyBy('id');
    }

    foreach ($freeVeichles as $v) {
        if ($i == 0) {
            /** @var Ride $r */
            //$r = array_pop($initialRides);
            $value = end($initialRides);
            $key = key($initialRides);
            unset($initialRides[$key]);
            $r = $RIDES->get($key);
        } else {
            $v->recalculateDistances($RIDES);
            do {
                //$r = array_pop($v->distancesFromRides);
                $value = end($v->distancesFromRides);
                $key = key($v->distancesFromRides);
                unset($v->distancesFromRides[$key]);
                $r = $RIDES->get($key);
                if (!$r) {
                    $VEHICLES->forget($v->id);
                    break;
                }
            } while ($v->distanceFromFinishingRide($r) > ($steps - $i));
        }
        if ($r) {
            $v->freeAt = $v->distanceFromFinishingRide($r);
            $v->currentC = $r->cFinish;
            $v->currentR = $r->rFinish;
            $RIDES->forget($r->id);
            if ($i + $v->distanceFromStartingRide($r) < $r->earliestStart) {
                $totalScore += $bonus;
            }
            $totalScore += $r->distance;
        }
    }

    foreach ($VEHICLES as $v) {
        if ($v->freeAt > 0) {
            $v->freeAt--;
        }
    }

    Log::out("Current step: {$i} - Remaining rides: " . count($RIDES) . " - Total score: {$totalScore}");
}
