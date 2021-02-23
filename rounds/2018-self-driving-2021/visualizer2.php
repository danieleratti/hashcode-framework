<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'd';

include 'reader.php';

/** @var Collection|Vehicle[] $VEHICLES */
/** @var Collection|Ride[] $RIDES */
/** @var int $rows */
/** @var int $columns */
/** @var int $vehicles */
/** @var int $rides */
/** @var int $bonus */
/** @var int $steps */

$ellipseSize = 20;

$visualStarts = new VisualStandard($rows, $columns);
$visualFinishes = new VisualStandard($rows, $columns);
foreach($RIDES as $ride) {
    $visualStarts->drawEllipse($ride->rStart, $ride->cStart, $ellipseSize, Colors::green5);
    $visualFinishes->drawEllipse($ride->rFinish, $ride->cFinish, $ellipseSize, Colors::red5);
}
$visualStarts->save($fileName."_ellipse_starts");
$visualFinishes->save($fileName."_ellipse_finishes");
