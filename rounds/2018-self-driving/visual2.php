<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'e';

include 'reader.php';

$visual = new VisualStandard($R, $C);

/** @var Ride $ride */
foreach ($rides as $ride) {
    $rMed = ($ride->rStart + $ride->rEnd) / 2;
    $cMed = ($ride->cStart + $ride->cEnd) / 2;

    $visual->setLine($ride->rStart, $ride->cStart, $rMed, $cMed, Colors::green5);
    $visual->setLine($rMed, $cMed, $ride->rEnd, $ride->cEnd, Colors::red5);
}

$visual->save('line_' . $fileName);
