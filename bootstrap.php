<?php

error_reporting(E_ERROR | E_PARSE);

// initialize composer
require_once __DIR__ . "/vendor/autoload.php";

// incrase the memory
ini_set('memory_limit', '-1');

function collect($value = null)
{
    return new Utils\Collection($value);
}

function issetOrVal(&$value, $orVal)
{
    return isset($value) ? $value : $orVal;
}

function issetOrNull(&$value)
{
    return issetOrVal($value, null);
}
