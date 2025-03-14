<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include 'reader.php';

$visualStandard = new VisualStandard($HEIGHT, $WIDTH);
foreach($tiles as $tile) {
    /** @var Tile $tile */
    if($tile->isDevDesk)
        $visualStandard->setPixel($tile->r, $tile->c, Colors::blue5);
    elseif($tile->isManagerDesk)
        $visualStandard->setPixel($tile->r, $tile->c, Colors::red5);
    else
        $visualStandard->setPixel($tile->r, $tile->c, Colors::black);
}
$visualStandard->save('map_' . $fileName);
