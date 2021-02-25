<?php

$fileName = 'b';
$outputName = 'mm-sol1_b_should_be_easy_176877.txt';

include 'reader.php';

$content = trim(file_get_contents(__DIR__ . '/output/' . $outputName));
$rows = explode("\n", $content);

if (count($rows) > $cars->count())
    die('troppi veicoli');

$points = 0;
foreach ($rows as $row) {
    $outRides = explode(' ', $row);
    array_shift($outRides);

    $car = new Car(0);
    foreach ($outRides as $ride) {
        if (!isset($rides[$ride]))
            die('ride usata due volte');

        $points += $car->takeRide($rides->get($ride));
    }
}

echo "BRAVO! punteggio $points";
