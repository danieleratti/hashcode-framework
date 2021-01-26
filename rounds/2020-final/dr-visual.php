<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

require 'dr-reader.php';


$visualStandard = new VisualStandard($H, $W);
foreach($MOUNT_POINTS as $mp)
    $visualStandard->setPixel($mp->y, $mp->x, Colors::blue9);
foreach($ASSEMBLY_POINTS as $ap)
    $visualStandard->setPixel($ap->y, $ap->x, Colors::red9);
$visualStandard->save($fileName);
