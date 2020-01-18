<?php

use Src\Utils\FileManager;
use Src\Utils\Visual;

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
$ROWS = 100;
$COLUMNS = 100;
$visual = new Visual($ROWS, $COLUMNS);

for ($row = 0; $row < $ROWS; $row++) {
    for ($col = 0; $col < $COLUMNS; $col++) {
        $visual->setGradient($row, $col, $row / $ROWS);
    }
}

$visual->save('prova');
