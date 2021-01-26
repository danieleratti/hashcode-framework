<?php

/** @noinspection PhpUndefinedVariableInspection */

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

require 'dr-reader.php';


$visualStandard = new VisualStandard($H, $W);
//foreach ($MOUNT_POINTS as $mp)
//    $visualStandard->setPixel($mp->y, $mp->x, Colors::blue9);
foreach ($ASSEMBLY_POINTS as $ap) {
    /** @var AssemblyPoint $ap */
    $color = Colors::brown0;
    if($ap->singles > 0 && $ap->starts == 0 && $ap->finishes == 0)
        $color = Colors::pink9;
    elseif($ap->starts > 0 && $ap->finishes == 0)
        $color = Colors::green9;
    elseif($ap->finishes > 0 && $ap->starts == 0)
        $color = Colors::red9;
    $visualStandard->setPixel($ap->y, $ap->x, $color);
}
$visualStandard->save($fileName . '-starters');
