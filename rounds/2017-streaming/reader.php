<?php

use Utils\FileManager;

require_once '../../bootstrap.php';


class Video
{
    public $size = 0;

    public function __construct($size)
    {
        $this->size = $size;
    }
}


class EndPoint
{
    public $cacheServers = [];
    public $latencyDataCenter = 0;
    public $requests = [];

    public function __construct($latencyDataCenter)
    {
        $this->latencyDataCenter = $latencyDataCenter;
    }

    public function addCacheServer($id, $latency)
    {
        $this->cacheServers[$id] = $latency;
    }

    public function addRequest($idVideo, $numReq)
    {
        $this->requests[$idVideo] = $numReq;
    }
}

class Request
{
    public $videoId = 0;
    public $endPointId = 0;
    public $numRequests = 0;

    public function __construct($videoId, $endPointId, $numRequests)
    {
        $this->videoId = $videoId;
        $this->endPointId = $endPointId;
        $this->numRequests = $numRequests;
    }
}

$fileName = 'a';


$videos = $endPoints = $requests = [];
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

    $endPoints[$i] = new EndPoint($latency);
    for ($j = 0; $j < $numCacheServersConnected; $j++) {
        list($cacheServerId, $latencyFromServerToEndpoint) = explode(' ', $fileRows[$i + $startingFrom + $j + 1]);
        /** @var EndPoint[] $endPoints */
        $endPoints[$i]->addCacheServer($cacheServerId, $latencyFromServerToEndpoint);
    }

    $startingFrom += $numCacheServersConnected;
}

$startingFrom += $numEndpoints;

for ($j = 0; $j < $numRequests; $j++) {
    list($idVideo, $idEndPoint, $nVideoRequests) = explode(' ', $fileRows[$j + $startingFrom]);
    $requests[] = new Request($idVideo, $idEndPoint, $nVideoRequests);
    $endPoints[$idEndPoint]->addRequest($idVideo, $nVideoRequests);
}

//print_r($endPoints);

// Endpoints
// Videos
// $requests

