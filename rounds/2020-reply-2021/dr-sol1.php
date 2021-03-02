<?php

use Utils\Cerberus;

require_once __DIR__ . '/../../bootstrap.php';

$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);

include __DIR__ . '/reader.php';

/** Stuff... */


echo "Qui";
