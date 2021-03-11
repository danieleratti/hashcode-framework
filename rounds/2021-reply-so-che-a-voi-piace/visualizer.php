<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include 'reader.php';

/** @var FileManager $fileManager */
/** @var Building[] $buildings */
/** @var Antenna[] $antenna */
/** @var int $W */
/** @var int $H */
/** @var int $totalBuildings */
/** @var int $totalAntennas */
/** @var int $finalReward */

$visualStarts = new VisualStandard($rows, $columns);
$visualFinishes = new VisualStandard($rows, $columns);
foreach($RIDES as $ride) {
    $visualStarts->setPixel($ride->rStart, $ride->cStart, Colors::green5);
    $visualFinishes->setPixel($ride->rFinish, $ride->cFinish, Colors::red5);
}
$visualStarts->save($fileName."_starts");
$visualFinishes->save($fileName."_finishes");
