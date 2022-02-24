<?php

use Utils\ArrayUtils;

$fileName = 'a';

/** @var Contributor[] */
global $contributors;

/** @var Project[] */
global $projects;

/** @var Output */
global $OUTPUT;
/* Reader */
include_once 'mm-reader.php';

$skills2Contributors = [];
foreach ($contributors as $name => $c) {
    /** @var Contributor $c */
    $skills2Contributors[$c->skills[0]] = $c;
}

function recalculateProjectsScore($t)
{
    global $projects;
    foreach ($projects as $p) {
        /** @var Project $p */
        $remainingTime = $p->expire - $p->duration - $t;
        if ($remainingTime <= 0) {
            $base = max(($p->award + $remainingTime) / $p->award, 0);
        } else {
            $base = (1 + ($t / ($p->expire - $p->duration))) / 2;
        }
        $p->score = $p->award / $p->duration * $base;
    }
    ArrayUtils::array_keysort_objects($projects, 'score', SORT_DESC);
}

foreach ($projects as $p) {
    /** @var Project $p */
    echo $p->name . " - " . $p->score . "\n";
}

die();

// Algo

echo $maxTime;
for ($t = 0; $t < $maxTime; $t++) {
    //echo $t;
}


die();

/*
ArrayUtils::array_keysort_objects($projects, 'expire', SORT_ASC);
*/

