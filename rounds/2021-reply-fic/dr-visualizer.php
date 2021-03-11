<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include __DIR__ . '/dr-reader.php';

/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$visualStarts = new VisualStandard($rows, $columns);
$visualFinishes = new VisualStandard($rows, $columns);
foreach ($RIDES as $ride) {
    $visualStarts->setPixel($ride->rStart, $ride->cStart, Colors::green5);
    $visualFinishes->setPixel($ride->rFinish, $ride->cFinish, Colors::red5);
}
$visualStarts->save($fileName . "_starts");
$visualFinishes->save($fileName . "_finishes");
