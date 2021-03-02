<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'f';

include __DIR__ . '/reader.php';

$visualStandard = new VisualStandard($rowsCount, $columnsCount);
$visualStandard->setBg(Colors::white);

for ($row = 0; $row < $rowsCount; $row++) {
    for ($col = 0; $col < $columnsCount; $col++) {
        if ($MAP[$row][$col]) {
            $visualStandard->setPixel($row, $col, $MAP[$row][$col]->type === 'M' ? Colors::red5 : Colors::blue5);
        }
    }
}

$visualStandard->save('standard_' . $fileName);
