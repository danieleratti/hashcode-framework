<?php

use Utils\Cerberus;

require_once '../../bootstrap.php';

$fileName = 'c';
Cerberus::runClient(['fileName' => $fileName]);

/** @var \Utils\Collection $managers */
/** @var \Utils\Collection $developers */
/** @var \Utils\Collection $tiles */
/** @var Tile[] $tiles */
/** @var int $numDevelopers */
/** @var int $numManagers */
/** @var int $WIDTH */
/** @var int $HEIGHT */

include 'reader.php';

///** @var \Utils\Collection $sortedDevelopers */
//$sortedDevelopers = $developers->sortBy(['']);


//foreach ($developers as $d) {
//    /** @var Developer $d */
//    foreach ($developers as $e) {
//        /** @var Developer $e */
//        $a = getPairScore($d, $e);
//    }
//    echo $d->id . "\n";
//}

/* Create groups */
include_once 'groups.inc.php';
/** @var \Utils\Collection $groups */

// Do things
$groups = $groups->sortByDesc('devCount');


echo "Created " . count($groups) . " groups\n\n";


$fileManager->output(getOutput());
