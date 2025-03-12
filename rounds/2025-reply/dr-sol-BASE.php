<?php

use JMGQ\AStar\DomainLogicInterface;
use Utils\ArrayUtils;
use Utils\Autoupload;
use Utils\Cerberus;
use Utils\FileManager;
use Utils\Log;
use JMGQ\AStar\AStar;

require_once __DIR__ . '/../../bootstrap.php';

global $fileName;
/** @var FileManager */
global $fileManager;

for($fileName=1;$fileName<=8;$fileName++) {
    echo "Eseguo $fileName\n";
    /* Config & Pre runtime */
#$fileName = '8';
    $_visualyze = false;
    $_analyze = false;
//$param1 = 1;
//Cerberus::runClient(['fileName' => $fileName, 'param1' => $param1]);

    /* Reader */
    include_once 'reader.php';

    /* Classes */

    /** @var int $initialCapital */
    global $initialCapital;
    /** @var int $resourcesCount */
    global $resourcesCount;
    /** @var int $turnsCount */
    global $turnsCount;
    /** @var Turn[] $turns */
    global $turns;
    /** @var Resource[] $resources */
    global $resources;

// FX
    function getOutput()
    {
        global $placedResources; // [$t] => [... lista ID]
        $output = [];
        ksort($placedResources);
        foreach ($placedResources as $time => $resources) {
            $_output = [];
            $_output[] = $time;
            $_output[] = count($resources);
            foreach ($resources as $r)
                $_output[] = $r;
            $output[] = implode(" ", $_output);
        }
        return implode("\n", $output);
    }

    function place($id, $t)
    {
        global $resources, $placedResources, $targetShape, $resource2shape;
        echo "Piazzo $id @ $t\n";
        $placedResources[$t][] = $id;
        foreach ($resource2shape[$id] as $_t => $delta) {
            $targetShape[$t + $_t] = max(0, $targetShape[$t + $_t] - $delta);
        }
        unset($resources[$id]);
    }

// RUN
    $SCORE = 0;
    Log::out("Run started...");


    /** QUI DEVI SCRIVERE L'ALGORITMO
     * */


    /** FINE ALGORITMO */

    $fileManager->outputV2(getOutput(), 'Unknown');
}