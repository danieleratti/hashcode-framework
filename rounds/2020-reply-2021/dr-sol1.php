<?php

use Utils\Cerberus;

require_once '../../bootstrap.php';

$fileName = 'a';
Cerberus::runClient(['fileName' => $fileName]);

include 'reader.php';

/** Stuff... */


echo "Qui";
