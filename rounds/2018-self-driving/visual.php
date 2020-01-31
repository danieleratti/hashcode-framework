<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'd';

include 'reader.php';

$visual = new VisualStandard($R, $C);

/** @var Ride $ride */
foreach ($rides as $ride) {
    $visual->setPixel($ride->rStart, $ride->cStart, Colors::green4);
    $visual->setPixel($ride->rEnd, $ride->cEnd, Colors::red4);
}

$visual->save($fileName);
