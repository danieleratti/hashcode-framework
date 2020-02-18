<?php

use Utils\Log;
use Utils\Stopwatch;

$fileName = 'c';
$topPercentage = 0.3;

require_once 'reader.php';

/* functions */
function alignLocalScores($videoId = null, $cacheId = null) // ricalcolare la videosBestLatency + allineare local score di ogni tupla & eliminare tuple con score negativi (cache già migliori esistenti x quella req)
{
    global $sw_alignLocalScores;
    //$sw_alignLocalScores->tik();

    /** @var Video[] $videos */
    global $tuple, $requests, $endpoints, $videos;

    /* recalc video best latency */
    if ($videoId) {
        /** @var Endpoint[] $endpoints */
        foreach ($endpoints as $endpoint) {
            if (isset($endpoint->requests[$videoId])) {
                if (isset($endpoint->caches2latency[$cacheId])) {
                    $bestLatency = min($endpoint->videosBestLatency[$videoId], $endpoint->caches2latency[$cacheId]);
                    $endpoint->videosBestLatency[$videoId] = $bestLatency;
                }
            }
        }
    }

    foreach ($tuple as $tupla) {
        /** @var Tupla $tupla */
        if (!isset($videoId) || $tupla->videoId == $videoId) {
            $tempoRisparmiatoRispettoBest = $endpoints[$tupla->endpointId]->videosBestLatency[$tupla->videoId] - $endpoints[$tupla->endpointId]->caches2latency[$tupla->cacheId];
            if ($tempoRisparmiatoRispettoBest < 0) {
                // fuck tupla
                $tuple->forget($tupla->id); // TODO: Controllare!!!!!!!
                continue;
            }

            // TODO: tuning con km e kp
            $localScore = (
                ($requests[$tupla->requestId]->numRequests * $tempoRisparmiatoRispettoBest)
                /
                ($videos[$tupla->videoId]->sizePerc * 100)
            );
            $tupla->localScore = $localScore;
        }
    }
    //$sw_alignLocalScores->tok(false);
}

function alignCaches() // eliminare le tuple con video che non stanno nella cache (per size)
{
    global $sw_alignCaches;
    //$sw_alignCaches->tik();

    /** @var Video[] $videos */
    /** @var Cache[] $caches */
    global $tuple, $caches, $videos;

    foreach ($tuple as $tupla) {
        /** @var Tupla $tupla */
        if ($videos[$tupla->videoId]->size > $caches[$tupla->cacheId]->storage) {
            // fuck tupla
            $tuple->forget($tupla->id); // TODO: Controllare!!!!!!!
        }
    }
    //$sw_alignCaches->tok(false);
}

function putVideoInCache($requestId, $videoId, $cacheId)
{
    global $recalculateScore;
    global $caches, $tuple, $SCORE;
    Log::out("Aggiungo il video $videoId alla cache $cacheId", 0);

    /** @var Cache[] $caches */
    $caches[$cacheId]->addVideo($videoId);

    // eliminare tutte le tuple delle richiesta $requestId, poiché ormai è fullfillata
    $tuple = $tuple->reject(function ($tupla) use ($requestId) {
        /** @var Tupla $tupla */
        return $tupla->requestId == $requestId;
    });

    alignLocalScores($videoId, $cacheId);
    //alignCaches();

    $recalculateScore++;
    if($recalculateScore%10==0) {
        alignTotalScore();
        Log::out("SCORE = " . $SCORE, 1);
    }
}

function alignTotalScore()
{
    global $sw_alignTotalScore;

    //$sw_alignTotalScore->tik();
    global $SCORE, $endpoints, $totalQuantityOfRequests;

    $sumOfLatenciesSaved = 0;

    /** @var Endpoint[] $endpoints */
    foreach ($endpoints as $endpoint) {
        foreach ($endpoint->requests as $request) {
            /** @var  Request $request */
            $latencySaved = $request->numRequests * ($endpoint->latencyDataCenter - $endpoint->videosBestLatency[$request->videoId]);
            $sumOfLatenciesSaved += $latencySaved;
        }
    }
    $SCORE = $sumOfLatenciesSaved / $totalQuantityOfRequests * 1000;
    //$sw_alignTotalScore->tok(false);
}


/* runtime */

$tuple = collect();

/** @var Endpoint[] $endpoints */
foreach ($endpoints as $endpoint) {
    foreach ($endpoint->requests as $request) {
        /** @var Request $request */

        $endpoint->videosBestLatency[$request->videoId] = $endpoint->latencyDataCenter;

        foreach ($endpoint->caches2latency as $cacheServerId => $latency) {
            if ($videos[$request->videoId]->size <= $cacheCapacity) {
                $tuple->add(new Tupla(
                    $request->requestId,
                    $request->videoId,
                    $request->endpointId,
                    $cacheServerId
                ));
            }
        }
    }
}

/* algorithm */
$sw_alignLocalScores = new Stopwatch('alignLocalScores');
$sw_alignCaches = new Stopwatch('alignCaches');
$sw_alignTotalScore = new Stopwatch('alignTotalScore');

alignLocalScores();

Log::out('Filename ' . $fileName);

/** @var Tupla $first */
$tuple = $tuple->sortByDesc('localScore')->take($tuple->count() * $topPercentage);
while ($first = $tuple->sortByDesc('localScore')->first()) {

    if($videos[$first->videoId]->size > $caches[$first->cacheId]->storage) {
        alignCaches();
        continue;
    }

    Log::out('Tuple rimanenti = ' . $tuple->count(), 3);
    putVideoInCache($first->requestId, $first->videoId, $first->cacheId);
    //$sw_alignLocalScores->printTime();
    //$sw_alignCaches->printTime();
    //$sw_alignTotalScore->printTime();
}

alignTotalScore();
Log::out("FINAL SCORE = " . $SCORE, 1);
