<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Contributor[] */
global $contributors;
/** @var Project[] */
global $projects;
/** @var int $contributorsCount */
global $contributorsCount;
/** @var int $projectsCount */
global $projectsCount;

$fileName = 'f';

/* Reader */
include_once 'reader.php';

$analyzer = new Analyzer($fileName, [
    'contributors_count' => $contributorsCount,
    'projects_count' => $projectsCount
]);

$analyzer->addDataset('contributors', $contributors, ['skills']);

$analyzer->addDataset('projects', $projects, ['duration', 'award', 'expire', 'roles']);

$analyzer->analyze();

die();
