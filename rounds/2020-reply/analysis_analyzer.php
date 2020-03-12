<?php

$fileName = 'a';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var \Utils\Collection $managers */
/** @var \Utils\Collection $developers */
/** @var \Utils\Collection $tiles */
/** @var Tile[] $tiles */
/** @var int $numDevelopers */
/** @var int $numManagers */
/** @var int $WIDTH */
/** @var int $HEIGHT */

$analyzer = new Analyzer($fileName, [
    'developers_count' => $numDevelopers,
    'managers_count' => $numManagers,
    'office_width' => $WIDTH,
    'office_height' => $HEIGHT,
]);
$analyzer->addDataset('managers', $managers->toArray(), ['bonus']);
$analyzer->addDataset('developers', $developers->toArray(), ['skills', 'bonus']);
$analyzer->addDataset('tiles', $tiles->toArray(), ['isDesk', 'isDevDesk', 'isManagerDesk']);

$analyzer->analyze();
