<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'b';

include 'dr-reader.php';

/** @var int $N_VIDEOS */
/** @var int $N_ENDPOINTS */
/** @var int $N_REQUESTS */
/** @var int $N_CACHES */
/** @var int $SIZE_CACHES */
/** @var Collection|Video[] $VIDEOS */
/** @var Collection|Endpoint[] $ENDPOINTS */
/** @var Collection|Request[] $ENDPOINTS */
/** @var Collection|Cache[] $CACHES */

foreach ($VIDEOS as $videoId => $video) {
    $video->recalculateCacheFillRates();
}

// Algo

Log::out("Fine");
