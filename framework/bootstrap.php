<?php

use Src\RoundManager;

error_reporting(E_ERROR | E_PARSE);

// incrase the memory
ini_set('memory_limit', '-1');

// initialize composer
require_once __DIR__ . '/../vendor/autoload.php';

return new RoundManager();
