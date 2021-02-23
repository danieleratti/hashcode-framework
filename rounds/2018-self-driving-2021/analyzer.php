<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;

$fileName = 'e';

include 'reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$analyzer = new Analyzer($fileName, [
    'rows' => $rows,
    'columns' => $columns,
    'rides' => $rides,
    'vehicles' => $vehicles,
    'bonus' => $bonus,
    'steps' => $steps,
]);
$analyzer->addDataset('rides', $RIDES, ['distance', 'earliestStart', 'latestFinish', 'timespan']);
$analyzer->analyze();

