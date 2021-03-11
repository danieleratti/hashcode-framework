<?php


use Utils\Analysis\Analyzer;
use Utils\Collection;

$fileName = 'f';

include 'rnd-reader.php';

/** @var MAP $MAP */
/** @var Collection $BUILDINGS */
/** @var Collection $ANTENNAS */
/** @var int $bonus */
/** @var int $NBUILDINGS */
/** @var int $NANTENNAS*/
/** @var int $REWARD */


$analyzer = new Analyzer($fileName, [
    'rows' => $MAP->height,
    'columns' => $MAP->width,
    'reward' => $REWARD,
    'nbuindings' => $NBUILDINGS,
    'nantennas' => $NANTENNAS,
]);
$analyzer->addDataset('buildings', $BUILDINGS, ['latency', 'connectionSpeedWeight']);
$analyzer->addDataset('antennas', $ANTENNAS, ['range', 'connectionSpeed']);
$analyzer->analyze();
