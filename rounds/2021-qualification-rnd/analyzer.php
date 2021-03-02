<?php

use Utils\Analysis\Analyzer;

$fileName = 'a';

include 'reader.php';

/** @var Map $MAP */
/** @var Developer[] $DEVELOPERS */
/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Company[] $COMPANIES */
/** @var Skill[] $SKILLS */

$analyzer = new Analyzer($fileName, [
    'developers' => $NDEVELOPERS,
    'project managers' => $NPROJECTMANAGERS,
    'width' => $WIDTH,
    'height' => $HEIGHT,
    'companies' => count($COMPANIES),
    'skills' => count($SKILLS),
]);
$analyzer->addDataset('developers', $DEVELOPERS, ['company', 'bonus', 'skills']);
$analyzer->addDataset('project managers', $PROJECTMANAGERS, ['company', 'bonus']);
$analyzer->addDataset('companies', $COMPANIES, ['mame', 'inDevelopers', 'inProjectManagers']);
$analyzer->addDataset('skills', $SKILLS, ['name', 'inDevelopers']);

$analyzer->analyze();