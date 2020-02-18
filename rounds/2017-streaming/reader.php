<?php

use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* classes */

class Video
{
    public $size = 0;
    public $sizePerc = 0;

    public function __construct($size)
    {
        global $cacheCapacity;
        $this->size = $size;
        $this->sizePerc = $size / $cacheCapacity;
    }
}


class Endpoint
{
    public $caches2latency = [];
    public $latencyDataCenter = 0;
    public $requests = [];
    public $videosBestLatency = []; //[videoId] => bestLatency (inizialmente DC)

    public function __construct($latencyDataCenter)
    {
        $this->latencyDataCenter = $latencyDataCenter;
    }

    public function addCacheServer($id, $latency)
    {
        $this->caches2latency[$id] = $latency;
    }

    public function addRequest(Request $request)
    {
        $this->requests[$request->videoId] = $request;
    }
}

class Request
{
    public static $incrementalId = 0;
    public $requestId = 0;
    public $videoId = 0;
    public $endpointId = 0;
    public $numRequests = 0;

    public function __construct($videoId, $endpointId, $numRequests)
    {
        $this->requestId = self::$incrementalId++;
        $this->videoId = $videoId;
        $this->endpointId = $endpointId;
        $this->numRequests = $numRequests;
    }
}

class Tupla
{
    public static $incrementalId = 0;
    public $id;
    public $requestId;
    public $videoId;
    public $endpointId;
    public $cacheId;
    public $localScore;

    public function __construct($requestId, $videoId, $endpointId, $cacheId)
    {
        $this->id = self::$incrementalId++;
        $this->requestId = $requestId;
        $this->videoId = $videoId;
        $this->endpointId = $endpointId;
        $this->cacheId = $cacheId;
    }
}

class Cache
{
    public $cacheId;
    public $storage;
    public $videos = [];

    public function __construct($cacheId)
    {
        global $cacheCapacity;
        $this->cacheId = $cacheId;
        $this->storage = $cacheCapacity;
    }

    public function addVideo($videoId)
    {
        global $videos;
        /** @var Video[] $videos */
        $this->videos[] = $videoId;
        if ($videos[$videoId]->size > $this->storage)
            die('Non puoi aggiungere il video ' . $videoId . ' (size=' . $videos[$videoId]->size . ') alla cache ' . $this->cacheId . ' (storage=' . $this->storage . ') perché non c\'è abbastanza spazio');
        $this->storage -= $videos[$videoId]->size;
    }
}

/* functions */
function alignLocalScores($videoId = null, $cacheId = null) // ricalcolare la videosBestLatency + allineare local score di ogni tupla & eliminare tuple con score negativi (cache già migliori esistenti x quella req)
{
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
}

function alignCaches($cacheId) // eliminare le tuple con video che non stanno nella cache (per size)
{
    /** @var Video[] $videos */
    /** @var Cache[] $caches */
    global $tuple, $caches, $videos;

    foreach ($tuple as $tupla) {
        /** @var Tupla $tupla */
        if ($tupla->cacheId == $cacheId && $videos[$tupla->videoId]->size > $caches[$tupla->cacheId]->storage) {
            // fuck tupla
            $tuple->forget($tupla->id); // TODO: Controllare!!!!!!!
        }
    }
}

function putVideoInCache($requestId, $videoId, $cacheId)
{
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
    alignCaches($cacheId);
    alignTotalScore();

    Log::out("SCORE = " . $SCORE, 1);
}

function alignTotalScore()
{
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
}

/* read input */

$SCORE = 0;
$totalQuantityOfRequests = 0;

$videos = $endpoints = $requests = $caches = [];
// Reading the inputs
$fileManager = new FileManager($fileName);
$fileContent = $fileManager->get();

$fileRows = explode("\n", $fileContent);
list($numVideos, $numEndpoints, $numRequests, $numCacheServers, $cacheCapacity) = explode(' ', $fileRows[0]);

$videoSizes = explode(" ", $fileRows[1]);

foreach ($videoSizes as $size) {
    $videos[] = new Video($size);
}

$startingFrom = 2;
for ($i = 0; $i < $numEndpoints; $i++) {
    list($latency, $numCacheServersConnected) = explode(' ', $fileRows[$i + $startingFrom]);

    $endpoints[$i] = new Endpoint($latency);
    for ($j = 0; $j < $numCacheServersConnected; $j++) {
        list($cacheServerId, $latencyFromServerToEndpoint) = explode(' ', $fileRows[$i + $startingFrom + $j + 1]);
        /** @var Endpoint[] $endpoints */
        $endpoints[$i]->addCacheServer($cacheServerId, $latencyFromServerToEndpoint);
    }

    $startingFrom += $numCacheServersConnected;
}

$startingFrom += $numEndpoints;

for ($j = 0; $j < $numRequests; $j++) {
    list($idVideo, $idEndPoint, $nVideoRequests) = explode(' ', $fileRows[$j + $startingFrom]);
    $request = new Request($idVideo, $idEndPoint, $nVideoRequests);
    $requests[] = $request;
    $endpoints[$idEndPoint]->addRequest($request);
    $totalQuantityOfRequests += $nVideoRequests;
}

for ($cacheId = 0; $cacheId < $numCacheServers; $cacheId++)
    $caches[] = new Cache($cacheId);

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
alignLocalScores();
