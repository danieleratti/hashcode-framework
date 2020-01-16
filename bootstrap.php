<?php

require_once "vendor/autoload.php";
ini_set('memory_limit', '8G');

use Src\Utils\Collection;

function collect($value = null)
{
    return new Collection($value);
}

function getExecutionTime(callable $function)
{
    $start = microtime(true);
    $function();
    return microtime(true) - $start;
}
