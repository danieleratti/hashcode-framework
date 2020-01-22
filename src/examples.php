<?php

use Src\Utils\FileManager;
use Src\Utils\Stopwatch;
use Src\Utils\Visual\Colors;
use Src\Utils\Visual\VisualGradient;
use Src\Utils\Visual\VisualStandard;

require_once '../bootstrap.php';

// Reading the inputs
$fileManager = new FileManager('a');
$fileManager->get();
$fileManager->output('Output');

// Iterating all the inputs
foreach (FileManager::listInputFiles() as $filename) {
    $fileManager = new \Src\Utils\FileManager($filename);
}

// Using the visual classes
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
        if (($row + $col) % 10 == 0 || ($row - $col) % 10 == 0)
            $visualStandard->setPixel($row, $col, Colors::red5);
    }
}
$visualStandard->save('standard');


// Check the execution times
$watcher = new Stopwatch('watcher1');
for ($i = 0; $i < 10; $i++) {
    $watcher->tik();
    usleep(100000);
    $watcher->tok();
}
