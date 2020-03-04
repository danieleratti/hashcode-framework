<?php

$fileName = 'b';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var int $maxWalkingDistance */
/** @var int $buildingPlansCount */
/** @var \Utils\Collection $residences */
/** @var \Utils\Collection $utilities */
/** @var City $city */

// Find best residence
/** @var Residence $bestResidence */
$bestResidence = null;
foreach ($residences as $r) {
    /** @var Residence $r */
    if ($r->efficiency == 1 && $r->capacity == 2) {
        $bestResidence = $r;
        break;
    }
}

// Find best utilities
/** @var Utility[] $bestUtilities */
foreach ($utilities as $u) {
    /** @var Utility $u */
    if ($u->width == 1 && $u->height == 1) {
        $bestUtilities[] = $u;
        if (count($bestUtilities) == 6) break;
    }
}

// Place
for ($r = 0; $r < 1000; $r++) {
    for ($c = 0; $c < 1000; $c += 2) {
        if (($r % 6 == 0 && $c % 4 == 0) || ($r % 6 == 3 && $c % 4 == 2)) {
            $city->placeBuilding($bestUtilities[0], $r, $c);
            $city->placeBuilding($bestUtilities[1], $r, $c + 1);
        } elseif (($r % 6 == 2 && $c % 4 == 0) || ($r % 6 == 5 && $c % 4 == 2)) {
            $city->placeBuilding($bestUtilities[2], $r, $c);
            $city->placeBuilding($bestUtilities[3], $r, $c + 1);
        } elseif (($r % 6 == 4 && $c % 4 == 0) || ($r % 6 == 1 && $c % 4 == 2)) {
            $city->placeBuilding($bestUtilities[4], $r, $c);
            $city->placeBuilding($bestUtilities[5], $r, $c + 1);
        } else {
            $city->placeBuilding($bestResidence, $r, $c);
        }
    }
}

// $city->print();
echo "Score: " . $city->getScore();
