<?php

use Utils\Cerberus;

require_once '../../bootstrap.php';

$fileName = 'd';
Cerberus::runClient(['fileName' => $fileName]);

include 'reader.php';

/** Stuff... */
