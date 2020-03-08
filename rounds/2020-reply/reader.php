<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

// Classes

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

//[$cityRows, $cityColumns, $maxWalkingDistance, $buildingPlansCount] = explode(' ', $content[0]);

foreach ($content as $rowNumber => $row) {
    if ($rowNumber > 0) { /* skip first */

    }
}
