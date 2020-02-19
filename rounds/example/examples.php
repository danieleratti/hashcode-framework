<?php

use Utils\FileManager;
use Utils\Log;
use Utils\Stopwatch;
use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

require_once '../../bootstrap.php';

// Reading the inputs
$fileManager = new FileManager('a');
$fileManager->get();
$fileManager->output('Output');

// Iterating all the inputs
foreach (FileManager::listInputFiles() as $filename) {
    $fileManager = new \Utils\FileManager($filename);
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

// Logging
Log::out('Output colored', 1, 'white', 'red');
Log::out('Output colored', 1, 'blue');

// Check the execution times
for ($i = 0; $i < 10; $i++) {
    Stopwatch::tik('w1');
    usleep(100000);
    Stopwatch::tok('w1');
}
Stopwatch::print('w1');
Stopwatch::print();
