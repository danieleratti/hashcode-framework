<?php

use Utils\DirUtils;
use Utils\FileManager;

class ReaderOutput
{
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function getResult()
    {
        $baseInputName = basename($this->fileManager->inputName, '.in');
        $scriptName = DirUtils::getScriptName();

        $content = trim(file_get_contents(__DIR__ . '/output/' . $scriptName . '_' . $baseInputName . '.txt'));
        $rows = explode("\n", $content);

        if (count($rows) > Initializer::$CARS->count()) {
            die('troppi veicoli');
        }

        $points = 0;
        foreach ($rows as $row) {
            $outRides = explode(' ', $row);
            array_shift($outRides);

            $car = new Car(0);
            foreach ($outRides as $ride) {
                //if (Initializer::$RIDES->get($ride)) {
                //    die('ride usata due volte');
                //}

                $points += $car->takeRide(Initializer::$RIDES->get($ride), $car->freeAt, false);
            }
        }

        echo "\nBRAVO! punteggio $points\n";
    }
}
