<?php

use Utils\Log;

// Soluzione specifica per B

$fileName = 'b';

include 'dr-reader.php';

/** @var int $N_VIDEOS */
/** @var int $N_ENDPOINTS */
/** @var int $N_REQUESTS */
/** @var int $N_CACHES */
/** @var int $SIZE_CACHES */
/** @var Video[] $VIDEOS */
/** @var Endpoint[] $ENDPOINTS */
/** @var Request[] $ENDPOINTS */
/** @var Cache[] $CACHES */
/** @var Request[] $REQUESTS */

$availableVideos = $VIDEOS;

// Clean useless videos
foreach ($availableVideos as $videoId => $video) {
    if (count($video->requests) === 0)
        unset($availableVideos[$videoId]);
}

$parkedVideos = [];

while (true) {

    // Calculate caches fill rate
    foreach ($availableVideos as $videoId => $video) {
        $video->recalculateCacheFillRates();
        // Park video with no caches
        if (count($video->cachesFillRates) === 0) {
            $parkedVideos[$videoId] = $video;
            unset($availableVideos[$videoId]);
        }
    }

    if (count($availableVideos) === 0) break;

    // Sort videos by variuos criteria (mezzo random)
    uasort($availableVideos, function (Video $v1, Video $v2) {
        if (count($v1->cachesFillRates) !== count($v2->cachesFillRates))
            return count($v1->cachesFillRates) < count($v2->cachesFillRates);
        if ($v1->cacheFullfillsCount !== $v2->cacheFullfillsCount)
            return $v1->cacheFullfillsCount > $v2->cacheFullfillsCount;
        return $v1->avgCachesFillRate > $v2->cachesFillRates[0];
    });

    // Take the best video and add it to a cache
    reset($availableVideos);
    $currentVideoId = key($availableVideos);
    $currentVideo = $availableVideos[$currentVideoId];
    $currentCacheId = key($currentVideo->cachesFillRates);
    $currentCache = $CACHES[$currentCacheId];
    $currentCache->videos[$currentVideoId] = $currentVideo;
    $currentCache->availableSize -= $currentVideo->size;
    $currentVideo->inCaches[] = $currentCache;
    $cacheMissed = false;
    foreach ($currentVideo->requests as $request) {
        $requestCacheMissed = true;
        foreach ($ENDPOINTS[$request->endpointId]->cacheLatencies as $cacheId => $cacheLatency) {
            $cache = $CACHES[$cacheId];
            if ($cache->videos[$request->videoId]) {
                $requestCacheMissed = false;
                break;
            }
        }
        if ($requestCacheMissed) {
            $cacheMissed = true;
            break;
        }
    }
    if (!$cacheMissed) {
        unset($availableVideos[$currentVideoId]);
    }
}

// Scoring
$score = 0;
$requestsCount = 0;
foreach ($REQUESTS as $request) {
    $requestsCount += $request->quantity;
    $currentScore = 0;
    //  Search for cache
    foreach ($ENDPOINTS[$request->endpointId]->cacheLatencies as $cacheId => $cacheLatency) {
        $cache = $CACHES[$cacheId];
        if ($cache->videos[$request->videoId]) {
            $currentScore = max($currentScore, $ENDPOINTS[$request->endpointId]->dcLatency - $cacheLatency);
        }
    }
    $score += $currentScore * $request->quantity * 1000;

    if ($currentScore > 0)
        Log::out("Request {$request->id} hits cache");
    else
        Log::out("[!] Request {$request->id} missed cache");
}
$score = floor($score / $requestsCount);

Log::out("Score: {$score}");

Log::out("Fine");

