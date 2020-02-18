<?php

use Utils\FileManager;
use Utils\Log;
use Utils\Stopwatch;

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
        global $STORAGE;
        global $videos;
        /** @var Video[] $videos */
        $this->videos[] = $videoId;
        if ($videos[$videoId]->size > $this->storage)
            die('Non puoi aggiungere il video ' . $videoId . ' (size=' . $videos[$videoId]->size . ') alla cache ' . $this->cacheId . ' (storage=' . $this->storage . ') perché non c\'è abbastanza spazio');
        $this->storage -= $videos[$videoId]->size;
        $STORAGE -= $videos[$videoId]->size;
    }
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

Log::out('Heating 1/3');

foreach ($videoSizes as $size) {
    $videos[] = new Video($size);
}

Log::out('Heating 2/3');

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

Log::out('Heating 3/3');

for ($j = 0; $j < $numRequests; $j++) {
    list($idVideo, $idEndPoint, $nVideoRequests) = explode(' ', $fileRows[$j + $startingFrom]);
    $request = new Request($idVideo, $idEndPoint, $nVideoRequests);
    $requests[] = $request;
    $endpoints[$idEndPoint]->addRequest($request);
    $totalQuantityOfRequests += $nVideoRequests;
}

for ($cacheId = 0; $cacheId < $numCacheServers; $cacheId++)
    $caches[] = new Cache($cacheId);

Log::out('Heated');
