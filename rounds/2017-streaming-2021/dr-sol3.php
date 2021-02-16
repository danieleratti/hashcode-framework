<?php

use Utils\ArrayUtils;
use Utils\Collection;
use Utils\Log;

$fileName = 'e';

include 'dr-reader.php';

/** @var int $N_VIDEOS */
/** @var int $N_ENDPOINTS */
/** @var int $N_REQUESTS */
/** @var int $N_CACHES */
/** @var int $SIZE_CACHES */
/** @var Collection|Video[] $VIDEOS */
/** @var Collection|Endpoint[] $ENDPOINTS */
/** @var Collection|Request[] $REQUESTS */
/** @var Collection|Cache[] $CACHES */

$ORIGINAL_VIDEOS = $VIDEOS;

$VIDEOS = collect($VIDEOS);
$VIDEOS->keyBy('id');

$ORIGINAL_VIDEOS = collect($ORIGINAL_VIDEOS);
$ORIGINAL_VIDEOS->keyBy('id');

$ENDPOINTS = collect($ENDPOINTS);
$ENDPOINTS->keyBy('id');

$REQUESTS = collect($REQUESTS);
$REQUESTS->keyBy('id');

$CACHES = collect($CACHES);
$CACHES->keyBy('id');

/** ulteriori vars */
$caches2videos = [];

/** functions */
function getScore()
{
    global $REQUESTS, $ORIGINAL_VIDEOS, $ENDPOINTS;
    $score = 0;
    $totQty = 0;
    foreach ($REQUESTS as $request) {
        $video = $ORIGINAL_VIDEOS[$request->videoId];
        $endpoint = $ENDPOINTS[$request->endpointId];
        $dcLatency = $endpoint->dcLatency;
        $saved = 0;
        foreach ($video->inCaches as $cache) {
            if ($endpoint->cacheLatencies[$cache->id])
                $saved = max($saved, ($dcLatency - $endpoint->cacheLatencies[$cache->id]));
        }
        $score += $saved * $request->quantity;
        $totQty += $request->quantity;
    }
    return floor($score / $totQty * 1000);
}

function calculateBestScore($video, $limitCombination=1)
{
    /** @var Video $video */
    global $ENDPOINTS;
    global $VIDEOS;
    global $CACHES;
    global $caches2videos;
    // considero solo le cache per cui:
    // A) c'è spazio per il video $videoId
    // B) sono presenti in almeno una richiesta legata a quel video
    // utilizzo nMaxCachesPerVideo

    // vars
    $possibleCacheIds = [];
    $scores = [];

    //ALGO
    $bestScore = 0;
    $bestCaches = [];
    // ciclo tutte le richieste e prendo gli endpoint (1:1 $endpoint = $ENDPOINTS[$request->endpointId];
    foreach ($video->requests as $request) {
        /** @var Request $request */
        /** @var Endpoint $endpoint */
        $endpoint = $ENDPOINTS[$request->endpointId];
        // ciclo tutte le caches connesse a quell'endpoint
        foreach ($endpoint->cacheLatencies as $cacheId => $cacheLatency) {
            /** @var Cache $cache */
            $cache = $CACHES[$cacheId];
            // se cache->size >= video->size
            if ($cache->size >= $video->size) {
                $possibleCacheIds[$cache->id] = true;
                $scores[$cacheId][$request->id] = $request->quantity * ($endpoint->dcLatency - $cacheLatency);
            }
        }
    }

    $possibleCacheIds = array_keys($possibleCacheIds); // keys to values
    $possibleCachesCombinations = ArrayUtils::getAllCombinationsFlatLimited($possibleCacheIds, $limitCombination);

    // ciclo tutte le $possibleCachesCombinations as $cachesCombination
    foreach ($possibleCachesCombinations as $cachesCombination) {
        $score = 0;
        // ciclo tutte le requests di quel video
        foreach ($video->requests as $request) {
            // ciclo tutte le $cachesCombination
            $bestMicroScore = 0;
            foreach ($cachesCombination as $cacheId)
                // prendo il maggiore tra $scores[$cachesCombination[i]][]
                $bestMicroScore = max($bestMicroScore, $scores[$cacheId][$request->id]);
            $score += $bestMicroScore;
        }
        // se $score > $bestScore
        //$score = $score / ($video->size * count($cachesCombination)); // TUNING
        $score = $score / ($video->size * pow(count($cachesCombination),0.5)); // TUNING
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestCaches = array_map(function ($cacheId) use ($CACHES) {
                return $CACHES[$cacheId];
            }, $cachesCombination);
        }
    }

    // cleaning
    if ($video->bestCaches) {
        foreach ($video->bestCaches as $cache)
            unset($caches2videos[$cache->id][$video->id]);
    }

    // refill
    foreach ($bestCaches as $cache) {
        $caches2videos[$cache->id][$video->id] = $video;
    }

    $video->bestScore = $bestScore; // score migliore: sommatoria_requests( qty * TRisparmiatoSuCacheMiglioreRispDC )/( pesovideo*nCaches )
    $video->bestCaches = $bestCaches; // cache usate (linkare oggetti?)

    // SE $bestScore == 0
    if ($bestScore == 0) {
        // forget video
        $VIDEOS->forget($video->id);
    }
}

function putVideoInCaches($video)
{
    global $VIDEOS, $caches2videos;
    $bestCacheIds = array_map(function ($cache) {
        return $cache->id;
    }, $video->bestCaches);
    Log::out("Put video " . $video->id . " into caches " . implode(",", $bestCacheIds));
    // deve metterlo nelle $video->caches
    // cleaning
    if ($video->bestCaches) {
        foreach ($video->bestCaches as $cache) {
            $video->inCaches[] = $cache;
            if ($cache->size >= $video->size) {
                $cache->videos[] = $video;
                $cache->size -= $video->size;
            } else {
                Log::error('Stai provando a mettere un video di size più alto del rimanente nella cache');
            }
            unset($caches2videos[$cache->id][$video->id]);
        }
    } else {
        Log::error('Stai provando a fare put su un video che non ha bestCaches');
    }

    $VIDEOS->forget($video->id);
    Log::out("SCORE = " . getScore() . " // RemainingVideos = " . count($VIDEOS));
}

// heating:
// ciclo tutti i video
Log::out("Calculating best scores (all)");
foreach ($VIDEOS as $videoId => $video) {
    // calcolo per ognuno la getBestScore() e metto il risultato in un attributo dell'oggetto Video
    Log::out("Calculating best scores with limit=1 (" . $videoId . "/" . count($VIDEOS) . ")");
    calculateBestScore($video);
}
// sortBy score DESC
Log::out("SortByDesc");
$VIDEOS->sortByDesc('bestScore');


// ciclo tutti i primi
/*
Log::out("Calculating best scores (first)");
foreach ($VIDEOS->get(100) as $videoId => $video) {
    Log::out("Calculating best scores with limit=2 (" . $videoId . "/" . count($VIDEOS) . ")");
    calculateBestScore($video, 2);
}
// sortBy score DESC
Log::out("SortByDesc");
$VIDEOS->sortByDesc('bestScore');
*/

// algoritmo:
// while(esistono video):
while (count($VIDEOS) > 0) {
    // prendo il primo video della lista ordinata (con score più alto)
    $bestVideo = $VIDEOS->first();
    calculateBestScore($bestVideo, 2);

    $bestVideoCaches = $bestVideo->bestCaches;

    // lo metto nelle caches selezionate dal bestScore
    putVideoInCaches($bestVideo);

    // ciclo tutti i video impattati dall'utilizzo delle N caches (ovvero quei video le cui caches selezionate, comparivano nei loro bestScore )
    foreach ($bestVideoCaches as $cache) {
        foreach ($caches2videos[$cache->id] as $video) {
            /** @var Video $video */
            // ricalcolo il bestScore per quel video
            $recalc = false;
            foreach($video->bestCaches as $bestCache)
            {
                if($bestCache->size < $video->size)
                {
                    $recalc = true;
                    break;
                }
            }
            if($recalc)
                calculateBestScore($video);
        }
    }
    // rifaccio la sortBy score DESC totale
    $VIDEOS->sortByDesc('bestScore');
}

Log::out("Fine");
