<?php

use Utils\Cerberus;
use Utils\Log;

require_once '../../bootstrap.php';

$fileName = null;
$seconds = null;
Cerberus::runClient(['fileName' => 'b', 'seconds' => 1.0]);

require_once 'reader.php';

/* Runtime */

Log::out("Run with params = $fileName, $seconds");

for ($i = 0; $i < $seconds * 10; $i++) {
    Log::out('SCORE=' . ($i / 10));
    usleep(100 * 1000);
}

Log::out("SCORE(" . $fileName . ")=" . ($i / 10));
$fileManager->output('test', 'score-' . $SCORE);
