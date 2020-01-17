<?php

// inizializzo composer
require_once "vendor/autoload.php";

// aumento limiti RAM disponibile
ini_set('memory_limit', '16G');

function collect($value = null)
{
    return new Src\Utils\Collection($value);
}
