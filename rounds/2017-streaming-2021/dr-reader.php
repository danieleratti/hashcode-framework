<?php

ini_set('display_errors', E_ERROR);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../../bootstrap.php';

use Utils\FileManager;
use Utils\Log;

$fileName = $fileName ?: 'a';

// Classes
class Video
{
    public $id;
    public $size;
    public $requests = [];

    public function __construct($id, $size)
    {
        global $VIDEOS;
        $this->id = $id;
        $this->size = (int)$size;
        $VIDEOS[$this->id] = $this;
    }
}

class Cache
{
    public $id;
    public $size;
    public $endpointLatencies = []; // [$endpointId] => $latencyToEndpoint

    public function __construct($id, $size)
    {
        global $CACHES;
        $this->id = $id;
        $this->size = (int)$size;
        $CACHES[$this->id] = $this;
    }
}

class Endpoint
{
    public $id;
    public $dcLatency;
    public $cacheLatencies = []; // [$cacheId] => $latencyToCache
    public $requests = [];

    public function __construct($id, $dcLatency, $cacheLatencies)
    {
        global $CACHES, $ENDPOINTS;
        $this->id = $id;
        $this->dcLatency = (int)$dcLatency;
        $this->cacheLatencies = $cacheLatencies;
        foreach ($cacheLatencies as $cacheId => $cacheLatency)
            $CACHES[$cacheId]->endpointLatencies[(int)$id] = (int)$cacheLatency;
        $ENDPOINTS[$this->id] = $this;
    }
}

class Request
{
    public static $lastId;
    public $id;
    public $quantity;
    public $videoId;
    public $endpointId;

    public function __construct($quantity, $videoId, $endpointId)
    {
        global $VIDEOS, $ENDPOINTS, $REQUESTS;
        $this->id = self::$lastId++;
        $this->quantity = (int)$quantity;
        $this->videoId = (int)$videoId;
        $this->endpointId = (int)$endpointId;
        $VIDEOS[(int)$videoId]->requests[] = $this;
        $ENDPOINTS[(int)$endpointId]->requests[] = $this;
        $REQUESTS[$this->id] = $this;
    }
}

// Variables
$N_VIDEOS = 0;
$N_ENDPOINTS = 0;
$N_REQUESTS = 0;
$N_CACHES = 0;
$SIZE_CACHES = 0;
$VIDEOS = [];
$ENDPOINTS = [];
$REQUESTS = [];
$CACHES = [];

// Reading the inputs
Log::out("Reading file");
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($N_VIDEOS, $N_ENDPOINTS, $N_REQUESTS, $N_CACHES, $SIZE_CACHES) = explode(" ", $content[0]);
for($i=0;$i<$N_CACHES;$i++)
    new Cache($i, $SIZE_CACHES);
foreach ($content[1] as $videoId => $videoSize) {
    new Video($videoId, $videoSize);
}
$r = 2;
$endpointId = 0;
while ($r < count($content)) {
    if ($endpointId < $N_ENDPOINTS) {
        list($dcLatency, $nCachesConnected) = explode(" ", $content[$r]);
        $r++;
        $cacheLatencies = [];
        for ($ci = 0; $ci < $nCachesConnected; $ci++) {
            list($cacheId, $cacheLatency) = explode(" ", $content[$r]);
            $r++;
            $cacheLatencies[$cacheId] = $cacheLatency;
        }
        new Endpoint($endpointId, $dcLatency, $cacheLatencies);
        $endpointId++;
    } else {
        // Request Descriptions
        list($videoId, $endpointId, $quantity) = explode(" ", $content[$r]);
        new Request($quantity, $videoId, $endpointId);
        $r++;
    }
}

//$foo = collect($foo);
//$foo->keyBy('id');

Log::out("Read finished");
