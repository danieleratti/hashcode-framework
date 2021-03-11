<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include __DIR__ . '/dr-reader.php';

/** @var int $W */
/** @var int $H */
/** @var int $buildingsCount */
/** @var int $antennasCount */
/** @var int $reward */
/** @var Building[] $BUILDINGS */
/** @var Antenna[] $ANTENNAS */

$visual = new VisualStandard($W, $H);
$visualGradient = new VisualGradient($W, $H);
$visualGradient2 = new VisualGradient($W, $H);
$maxSpeed = 0;
$maxLatency = 0;
foreach ($BUILDINGS as $building) {
    $maxSpeed = max($maxSpeed, $building->speed);
    $maxLatency = max($maxLatency, $building->latency);
}
foreach ($BUILDINGS as $building) {
    $visual->setPixel($building->r, $building->c, Colors::green5);
    $visualGradient->setPixel($building->r, $building->c, $building->speed / $maxSpeed - 000.1);
    $visualGradient2->setPixel($building->r, $building->c, $building->latency / $maxLatency - 000.1);
}
$visual->save($fileName . "_buildings");
$visualGradient->save($fileName . "_speedweight");
$visualGradient2->save($fileName . "_latencyweight");

// verde = poco, rosso = tanto
