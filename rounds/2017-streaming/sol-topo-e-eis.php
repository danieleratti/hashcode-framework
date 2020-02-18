<?php

use Utils\ArrayUtils;
use Utils\Log;

/*
    e, 0.01, 1000 => KO
*/

$fileName = 'e';
$topPercentage = 0.01;
$takeChunk = 1000;

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
    Log::out('Tupling endpoint ' . $endpointId . '/' . count($endpoints));
    foreach ($endpoint->requests as $request) {
        /** @var Request $request */
        $endpoint->videosBestLatency[$request->videoId] = $endpoint->latencyDataCenter;

        /*
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
        */
        foreach ($endpoint->caches2latency as $cacheServerId => $latency) {
            $VC[$request->videoId][$cacheServerId]['n'] += $request->numRequests;
            $VC[$request->videoId][$cacheServerId]['cache_latency'] += $latency;
            $VC[$request->videoId][$cacheServerId]['dc_latency'] += $endpoint->latencyDataCenter;
        }
    }
}

$VCFlat = [];
foreach ($VC as $videoId => $_caches) {
    foreach ($_caches as $cacheId => $vc) {
        $vc['videoId'] = $videoId;
        $vc['cacheId'] = $cacheId;
        
        $vc['score'] = pow($vc['n'], 2);
        $vc['score'] *= pow($vc['dc_latency'], 1);
        $vc['score'] /= pow($vc['cache_latency'], 1);
        $vc['score'] /= pow($videos[$videoId]->sizePerc*100, 0.5);
        
        /** @var Video[] $videos */
        $VCFlat[] = $vc;
    }
}

$sumVideosSize = 0;
foreach ($videos as $video) {
    $sumVideosSize += $video->size;
}
Log::out('STORAGE = ' . $STORAGE);
Log::out('SUM(VIDEOS[SIZE]) = ' . $sumVideosSize);
Log::out('VC = ' . count($VCFlat));

$videosDone = [];
ArrayUtils::array_keysort($VCFlat, 'score', SORT_DESC);

Log::out("INITIAL SCORE = " . $SCORE, 1);

foreach ($VCFlat as $idxvc => $vc) {
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
Log::out("FINAL SCORE ($fileName) = " . $SCORE, 1);
