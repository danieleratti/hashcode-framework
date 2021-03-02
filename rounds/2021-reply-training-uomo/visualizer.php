<?php

use Utils\Analysis\Analyzer;
use Utils\Collection;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

include 'reader-seb.php';

/** @var FileManager $fileManager */
/** @var Employee[] $employees */
/** @var Employee[] $developers */
/** @var Employee[] $managers */
/** @var int[] $companies */
/** @var string[][] $office */
/** @var int $width */
/** @var int $height */
/** @var int $numDevs */
/** @var int $numProjManager */

$visual = new VisualStandard($height, $width);
for($i = 0; $i < $height; $i++) {
    for($j = 0; $j < $width; $j++) {
        if($office[$i][$j] == "#") {
            $visual->setPixel($i, $j, Colors::black);
        } elseif ($office[$i][$j] == "M") {
            $visual->setPixel($i, $j, Colors::red5);
        } elseif ($office[$i][$j] == "_") {
            $visual->setPixel($i, $j, Colors::blue5);
        }
    }
}
$visual->save($fileName);
