<?php

use Utils\Cerberus;
use Utils\Log;

require_once '../../bootstrap.php';

$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);

include 'reader.php';

/** @var Developer[] $developers */
/** @var Manager[] $managers */


$developers[0]->occupy(1, 1);
$developers[1]->occupy(1, 4);
$developers[4]->occupy(2, 3);
$developers[5]->occupy(2, 4);

$managers[0]->occupy(2, 1);
$managers[2]->occupy(2, 2);

Log::out('SCORE = '.getScore());

$fileManager->output(getOutput());
