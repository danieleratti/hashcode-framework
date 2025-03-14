<?php

use Utils\Analysis\Analyzer;

$fileName = 'd';

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
    'devCells' => $MAP->developersCell,
    'managerCells' => $MAP->managerCells,
    'companies' => count($COMPANIES),
    'skills' => count($SKILLS),
]);
$analyzer->addDataset('developers', $DEVELOPERS, [ 'bonus', 'skills']);
$analyzer->addDataset('project managers', $PROJECTMANAGERS, [ 'bonus']);
$analyzer->addDataset('companies', $COMPANIES, [ 'inDevelopers', 'inProjectManagers']);
$analyzer->addDataset('skills', $SKILLS, ['inDevelopers']);

$analyzer->analyze();