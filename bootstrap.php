<?php

use Utils\Collection;

error_reporting(E_ERROR | E_PARSE);
//ini_set('display_errors', 0);

// initialize composer
require_once __DIR__ . "/vendor/autoload.php";

// increase the memory
ini_set('memory_limit', '-1');

function collect($value = null): Collection
{
    return new Utils\Collection($value);
}

function issetOrVal($value, $orVal)
{
    return $value ?? $orVal;
}

function issetOrNull($value)
{
    return issetOrVal($value, null);
}
