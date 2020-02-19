<?php

use Utils\ArrayUtils;
use Utils\Log;

/*
    e, 1000, 1 => phpstorm running
*/

$fileName = 'e';
$iterationChunk = 10000000;
$powSizePercDenom = 1;

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

function putVideoInCache($videoId, $cacheId)
{
    global $caches, $tuple, $SCORE;
    Log::out("Aggiungo il video $videoId alla cache $cacheId", 0);

    /** @var Cache[] $caches */
    $caches[$cacheId]->addVideo($videoId);
    //alignLocalScores($videoId, $cacheId);
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

$STORAGE = $numCacheServers * $cacheCapacity;

Log::out('Adding tuples');

$tuple = collect();

$VC = [];

/** @var Endpoint[] $endpoints */
foreach ($endpoints as $endpointId => $endpoint) {
    Log::out('Heating endpoint ' . $endpointId . '/' . count($endpoints));
    foreach ($endpoint->requests as $request) {
        /** @var Request $request */
        $endpoint->videosBestLatency[$request->videoId] = $endpoint->latencyDataCenter;
    }
}


Log::out("INITIAL SCORE = " . $SCORE, 1);


// ITERATION
$_STORAGE = 0;
while($STORAGE != $_STORAGE) {
    $_STORAGE = $STORAGE;
    /** @var Endpoint[] $endpoints */
    foreach ($endpoints as $endpointId => $endpoint) {
        Log::out('Tupling endpoint ' . $endpointId . '/' . count($endpoints));
        foreach ($endpoint->requests as $request) {
            /** @var Request $request */
            foreach ($endpoint->caches2latency as $cacheServerId => $latency) {
                $VC[$request->videoId][$cacheServerId]['latency_saved'] += $request->numRequests * max(0, $endpoint->videosBestLatency[$request->videoId] - $latency);
            }
        }
    }

    $VCFlat = [];
    foreach ($VC as $videoId => $_caches) {
        foreach ($_caches as $cacheId => $vc) {
            $vc['videoId'] = $videoId;
            $vc['cacheId'] = $cacheId;
            $vc['score'] = $vc['latency_saved'];
            /** @var Video[] $videos */
            if ($vc['score'] > 0) {
                $vc['score'] /= pow($videos[$videoId]->sizePerc * 100, $powSizePercDenom);
                $VCFlat[] = $vc;
            }
        }
    }

    $videosDone = [];
    ArrayUtils::array_keysort($VCFlat, 'score', SORT_DESC);

    foreach ($VCFlat as $idxvc => $vc) {
        if(count($videosDone) == $iterationChunk) break;
        if (!$videosDone[$vc['videoId']]) {

            if ($videos[$vc['videoId']]->size > $caches[$vc['cacheId']]->storage) {
                //Log::out('AlignCaches perché size(video(' . $vc['videoId'] . '))=' . $videos[$vc['videoId']]->size . ' > storage(cache(' . $vc['cacheId'] . '))=' . $caches[$vc['cacheId']]->storage);
                alignCaches();
                continue;
            }

            Log::out('idxvc = ' . $idxvc . '/' . count($VCFlat));
            $videosDone[$vc['videoId']]++;
            putVideoInCache($vc['videoId'], $vc['cacheId']);
            alignLocalScores($vc['videoId'], $vc['cacheId']);

            if (count($videosDone) % 10 == 0) {
                alignTotalScore();
                Log::out("SCORE = " . $SCORE . " // STORAGE = " . $STORAGE, 1);
            }
        }
    }
    alignTotalScore();
    Log::out("PREFINAL SCORE ($fileName) = " . $SCORE, 1);
}


alignTotalScore();
Log::out("FINAL SCORE ($fileName) = " . $SCORE, 1);
