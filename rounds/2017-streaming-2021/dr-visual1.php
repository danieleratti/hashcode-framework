<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Log;

$fileName = 'a';

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

$analyzer = new Analyzer($fileName, [
    'n_videos' => $N_VIDEOS,
    'n_endpoints' => $N_ENDPOINTS,
    'n_requests' => $N_REQUESTS,
    'n_caches' => $N_CACHES,
    'size_caches' => $SIZE_CACHES,
]);

$analyzer->addDataset('videos', $VIDEOS, ['size', 'requests']);
$analyzer->addDataset('endpoints', $ENDPOINTS, ['dcLatency', 'cacheLatencies', 'requests']);
$analyzer->addDataset('requests', $REQUESTS, ['quantity', 'videoId', 'endpointId']);
$analyzer->addDataset('caches', $REQUESTS, ['size', 'videos', 'endpointLatencies']);

$analyzer->analyze();

Log::out("Fine");
