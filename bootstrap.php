<?php

// initialize composer
require_once "vendor/autoload.php";

// incrase the memory
ini_set('memory_limit', '-1');

function collect($value = null)
{
    return new Src\Utils\Collection($value);
}
