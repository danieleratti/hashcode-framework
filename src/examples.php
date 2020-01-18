<?php

use Src\Utils\FileManager;
use Src\Utils\Visual\Colors;
use Src\Utils\Visual\VisualGradient;
use Src\Utils\Visual\VisualStandard;

require_once '../bootstrap.php';

// LEGGERE GLI INPUT
$fileManager = new FileManager('a');
$fileManager->get();
$fileManager->output('Output');

// CICLARE SU TUTTI GLI INPUT
foreach (FileManager::listInputFiles() as $filename) {
    $fileManager = new \Src\Utils\FileManager($filename);
}

// USARE CLASSE VISUAL
$ROWS = 200;
$COLUMNS = 200;


$visualGradient = new VisualGradient($ROWS, $COLUMNS);
for ($row = 0; $row < $ROWS; $row++) {
    for ($col = 0; $col < $COLUMNS; $col++) {
        $visualGradient->setPixel($row, $col, $row / $ROWS);
    }
}
$visualGradient->save('gradiente');


$visualStandard = new VisualStandard($ROWS, $COLUMNS);
for ($row = 0; $row < $ROWS; $row++) {
    for ($col = 0; $col < $COLUMNS; $col++) {
        $visualStandard->setPixel($row, $col, Colors::red5);
    }
}
$visualStandard->save('standard');
