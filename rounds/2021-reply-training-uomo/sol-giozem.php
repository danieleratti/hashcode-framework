<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = null;
$param1 = null;
Cerberus::runClient(['fileName' => 'a', 'param1' => 1.0]);
// Autoupload::init();

include 'reader-seb.php';

/* VARIABLES */
/** @var FileManager $fileManager */
/** @var Employee[] $employees */
/** @var Employee[] $developers */
/** @var Employee[] $managers */
/** @var int[] $companies */
/** @var string[][] $office */
/** @var int $width */
/** @var int $height */
/** @var int $numDevs */
/** @var int $numProjManager */

$SCORE = 0;

/* ALGO */
Log::out("Run with fileName $fileName");
$descCompanies = $companies;
array_multisort($descCompanies, SORT_DESC, array_keys($descCompanies));
$mostPopularCompany = array_keys($descCompanies)[0];
$ascCompanies = $companies;
array_multisort($ascCompanies, SORT_ASC, array_keys($descCompanies), 'bonus');
$worstPopularCompany = array_keys($ascCompanies)[0];
$keys = array_keys($developers);

function employeeCompare($a, $b)
{
    return ($a->bonus + $a->numSkills) > ($b->bonus + $b->numSkills);
}

usort($developers, "employeeCompare");
usort($managers, "employeeCompare");

$keys = array_keys($managers);
array_multisort(
    array_column($managers, 'bonus'), SORT_DESC, SORT_NUMERIC, $managers, $keys
);

$managers = array_combine($keys, $managers);

// Map of unavailable places of the office
$officeUnavailable = [];
for ($i = 0; $i < $height; $i++) {
    for ($j = 0; $j < $width; $j++) {
        if ($office[$i][$j] == "#") {
            $officeUnavailable[$i][$j] = 1;
        } else {
            $officeUnavailable[$i][$j] = 0;
        }
    }
}

function isMapFull()
{
    global $officeUnavailable, $width, $height;
    $sum = 0;
    for ($i = 0; $i < $height; $i++) {
        for ($j = 0; $j < $width; $j++) {
            $sum += $officeUnavailable[$i][$j];
        }
    }

    return $sum == ($width * $height);
}

function findFirstDeskAvailable()
{
    global $officeUnavailable, $width, $height;
    for ($i = 0; $i < $height; $i++) {
        for ($j = 0; $j < $width; $j++) {
            if ($officeUnavailable[$i][$j] == 0) {
                return [$i, $j];
            }
        }
    }

    return null;
}

function findBestEmployee($isDev)
{
    global $developers, $managers;
    $employees = $isDev ? $developers : $managers;

    $best = null;
    foreach ($employees as $employee) {
        if ($employee->isAvailable()) {
            return $employee;
        }
    }

    return null;
}

function findBestCoworker($employee, $isDev) {
    global $developers, $managers;
    $employees = $isDev ? $developers : $managers;

    $coworkers = array_filter($employees, function($e) use ($employee) {
        return $e->isAvailable() && $e->company == $employee->company;
    });

    $best = null;
    $bestScore = 0;
    foreach ($coworkers as $coworker) {
        $intersection = array_intersect($coworker->skills, $employee->skills);
        $skillsScore = count($intersection) - count(array_diff(array_unique(array_merge($coworker->skills, $employee->skills)), $intersection));
        $bonus = $coworker->bonus * $employee->bonus;
        $score = $skillsScore + $bonus;

        if($best == null || $score > $bestScore) {
            $best = $coworker;
            $bestScore = $score;
        }
    }

    return $best;
}

function spreadCompany($r, $c, $employee)
{
    global $office, $officeUnavailable, $width, $height;
    // UP
    if ($r - 1 >= 0) {
        if ($officeUnavailable[$r - 1][$c] == 0) {
            $isDev = $office[$r - 1][$c] == '_';
            $best = findBestCoworker($employee, $isDev);
            if($best == null) {
                return;
            }
            $best->coordinates = [$r - 1, $c];
            $officeUnavailable[$r - 1][$c] = 1;
            spreadCompany($r - 1, $c, $best);
        }
    }
    // LEFT
    if ($c - 1 >= 0) {
        if ($officeUnavailable[$r][$c-1] == 0) {
            $isDev = $office[$r][$c-1] == '_';
            $best = findBestCoworker($employee, $isDev);
            if($best == null) {
                return;
            }
            $best->coordinates = [$r, $c-1];
            $officeUnavailable[$r][$c-1] = 1;
            spreadCompany($r, $c-1, $best);
        }
    }
    // BOTTOM
    if ($r + 1 <= $height) {
        if ($officeUnavailable[$r + 1][$c] == 0) {
            $isDev = $office[$r + 1][$c] == '_';
            $best = findBestCoworker($employee, $isDev);
            if($best == null) {
                return;
            }
            $best->coordinates = [$r + 1, $c];
            $officeUnavailable[$r + 1][$c] = 1;
            spreadCompany($r + 1, $c, $best);
        }
    }
    // RIGHT
    if ($c + 1 <= $width) {
        if ($officeUnavailable[$r][$c+1] == 0) {
            $isDev = $office[$r][$c+1] == '_';
            $best = findBestCoworker($employee, $isDev);
            if($best == null) {
                return;
            }
            $best->coordinates = [$r, $c+1];
            $officeUnavailable[$r][$c+1] = 1;
            spreadCompany($r, $c+1, $best);
        }
    }
}

while (!isMapFull()) {
    Log::out('Placing first');
    list($r, $c) = findFirstDeskAvailable();
    $isDev = $office[$r][$c] == '_';
    $bestEmployee = findBestEmployee($isDev);
    $bestEmployee->coordinates = [$r, $c];
    $officeUnavailable[$r][$c] = 1;
    spreadCompany($r, $c, $bestEmployee);
    Log::out('Placed first group');
}

usort($developers, function ($a, $b) {
    return $a->id > $b->id;
});
usort($managers, function ($a, $b) {
    return $a->id > $b->id;
});

/* OUTPUT */
Log::out('Output...');
$output = '';
foreach ($developers as $dev) {
    if($dev->isAvailable()) {
        $output .= 'X' . PHP_EOL;
    } else {
        $output .= $dev->coordinates[0] . ' ' . $dev->coordinates[1] . PHP_EOL;
    }
}
foreach ($managers as $man) {
    if($man->isAvailable()) {
        $output .= 'X' . PHP_EOL;
    } else {
        $output .= $man->coordinates[0] . ' ' . $man->coordinates[1] . PHP_EOL;
    }
}
$fileManager->outputV2($output, 'score_' . $SCORE);
// Autoupload::submission($fileName, null, $output);
