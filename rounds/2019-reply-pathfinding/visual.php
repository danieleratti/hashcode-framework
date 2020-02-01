<?php

use Utils\Visual\VisualGradient;

$fileName = '5';

$skipRead = true;

include 'reader.php';

$visualGradient = new VisualGradient($map->rowCount, $map->colCount);
for ($row = 0; $row < $map->rowCount; $row++) {
    for ($col = 0; $col < $map->colCount; $col++) {
        $cell = $map->cells[$row][$col];
        $cost = 0;
        switch ($cell->cost) {
            case 800:
                $cost = 0.9;
                break;
            case 200:
                $cost = 0.6;
                break;
            case 150:
                $cost = 0.5;
                break;
            case 120:
                $cost = 0.4;
                break;
            case 100:
                $cost = 0.3;
                break;
            case 70:
                $cost = 0.2;
                break;
            case 50:
                $cost = 0.1;
                break;
        }
        $visualGradient->setPixel($row, $col, $cell->utilizable ? ($cost) : 1);
    }
}

/** @var Client $client */
foreach ($clients as $client) {
    $visualGradient->setCustomPixel($client->row, $client->col, 255, 0, 255);
}

$visualGradient->save('map_' . $fileName);
