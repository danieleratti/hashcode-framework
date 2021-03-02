<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Graph;

$fileName = 'a';

include 'reader-seb.php';

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

$totalSkills = [];
foreach ($employees as $employee) {
    foreach($employee->skills as $skill){
        if(!in_array($skill, $totalSkills, true)){
            array_push($totalSkills, $skill);
        }
    }
}

$analyzer = new Analyzer($fileName, [
    'width' => $width,
    'height' => $height,
    'numDevs' => $numDevs,
    'numProjManager' => $numProjManager,
    'numSkills' => count($totalSkills),
]);
$analyzer->addDataset('developers', $developers, ['bonus', 'skills']);
$analyzer->addDataset('managers', $managers, ['bonus']);
$analyzer->addDataset('companies', $companies, []);
$analyzer->analyze();
