<?php

$fileName = 'b';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var int $maxWalkingDistance */
/** @var int $buildingPlansCount */
/** @var \Utils\Collection $residences */
/** @var \Utils\Collection $utilities */

foreach ($residences as $r) {
    /** @var Residence $r */
    if ($r->efficiency >= 0.75) {
        echo "Efficiency {$r->efficiency} for this with capacity {$r->capacity}:\n";
        echo "$r\n\n";
    }
}
