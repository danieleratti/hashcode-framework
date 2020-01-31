<?php

$fileName = 'd';
//$outputName = 'e-20200126161501.out';

include 'reader.php';

$content = trim(file_get_contents('/Users/matteomilesi/Dev/hashcode-framework/rounds/2018-self-driving-mm/output/e-20200127010118.out'));
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
