<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

$fileName = 'f';

include __DIR__ . '/reader.php';

global $snakes;
global $map;
global $rowsCount;
global $columnsCount;
global $snakesCount;

$visualGradient = new VisualGradient($rowsCount, $columnsCount);

$maxAbsScore = 0;

for ($c = 0; $c < $columnsCount; $c++) {
    for ($r = 0; $r < $rowsCount; $r++) {
        $maxAbsScore = max(abs($maxAbsScore), $map[$r][$c]);
    }
}

for ($c = 0; $c < $columnsCount; $c++) {
    for ($r = 0; $r < $rowsCount; $r++) {
        $v = $map[$r][$c];
        if ($v === '.') {
            $color = [0x00, 0x00, 0xff];
        } elseif ($v === '*') {
            $color = [0xff, 0xff, 0xff];
        } elseif ($v > 0) {
            $color = [0x00, $v / $maxAbsScore * 0xff, 0x00];
        } elseif ($v < 0) {
            $color = [-$v / $maxAbsScore * 0xff, 0x00, 0x00];
        } else {
            $color = [0x00, 0x00, 0x00];
        }
        $visualGradient->setCustomPixel($r, $c, ...$color);
    }
}

$visualGradient->save($fileName);

// verde = poco, rosso = tanto
