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
    /** @var int $id */
    public $id;
    /** @var int $size */
    public $size;
    /** @var Request[] $requests */
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
    /** @var int $id */
    public $id;
    /** @var int $size */
    public $size;
    /** @var int[] $endpointLatencies */
    public $endpointLatencies = []; // [$endpointId] => $latencyToEndpoint
    /** @var Video[] $videos */
    public $videos;

    public function __construct($id, $size)
    {
        global $CACHES;
        $this->id = $id;
        $this->size = (int)$size; // remaining size
        $this->videos = [];
        $CACHES[$this->id] = $this;
    }
}

class Endpoint
{
    /** @var int $id */
    public $id;
    /** @var int $dcLatency */
    public $dcLatency;
    /** @var int[] $cacheLatencies */
    public $cacheLatencies = []; // [$cacheId] => $latencyToCache
    /** @var Request[] $requests */
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
    /** @var int $lastId */
    public static $lastId = 0;
    /** @var int $id */
    public $id;
    /** @var int $quantity */
    public $quantity;
    /** @var int $videoId */
    public $videoId;
    /** @var int $endpointId */
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

[$N_VIDEOS, $N_ENDPOINTS, $N_REQUESTS, $N_CACHES, $SIZE_CACHES] = explode(" ", $content[0]);
$N_VIDEOS = (int)$N_VIDEOS;
$N_ENDPOINTS = (int)$N_ENDPOINTS;
$N_REQUESTS = (int)$N_REQUESTS;
$N_CACHES = (int)$N_CACHES;
$SIZE_CACHES = (int)$SIZE_CACHES;
for ($i = 0; $i < $N_CACHES; $i++)
    new Cache($i, $SIZE_CACHES);
foreach (explode(" ", $content[1]) as $videoId => $videoSize) {
    new Video($videoId, $videoSize);
}
$r = 2;
$_endpointId = 0;
while ($r < count($content)) {
    if ($_endpointId < $N_ENDPOINTS) {
        [$dcLatency, $nCachesConnected] = explode(" ", $content[$r]);
        $r++;
        $cacheLatencies = [];
        for ($ci = 0; $ci < $nCachesConnected; $ci++) {
            [$cacheId, $cacheLatency] = explode(" ", $content[$r]);
            $cacheId = (int)$cacheId;
            $cacheLatency = (int)$cacheLatency;
            $r++;
            $cacheLatencies[$cacheId] = $cacheLatency;
        }
        new Endpoint($_endpointId, $dcLatency, $cacheLatencies);
        $_endpointId++;
    } else {
        // Request Descriptions
        [$videoId, $endpointId, $quantity] = explode(" ", $content[$r]);
        $videoId = (int)$videoId;
        $endpointId = (int)$endpointId;
        $quantity = (int)$quantity;
        new Request($quantity, $videoId, $endpointId);
        $r++;
    }
}

//$foo = collect($foo);
//$foo->keyBy('id');

Log::out("Read finished");
