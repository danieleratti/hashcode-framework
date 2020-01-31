<?php

error_reporting(0);

$fileName = '5';

include 'reader.php';

$scores = [];

for ($c = 0; $c < $colCount; $c++) {
    for ($r = 0; $r < $rowCount; $r++) {
        foreach ($caches as $cache) {
            /** @var PathMap $cache */
            if (isset($cache->cells[$r]) && isset($cache->cells[$r][$c]) && $cache->cells[$r][$c]->pathCost > 0) {
                $scores["$r,$c"] += $cache->client->revenue - $cache->cells[$r][$c]->pathCost;
            }
        }
    }
}
arsort($scores);

$final = array_slice($scores, 0, $maxOfficesCount);
print_r($final);

$taken = [];
$offices = [];
$disconnectedClients = [];
for ($i = 0; $i < $clientsCount; $i++) {
    $disconnectedClients[$i] = true;
}

$bonus = collect($clients)->sum('revenue');

foreach ($caches as $clientCache) {
    /** @var PathMap $clientCache */
    foreach ($final as $rc => $s) {
        list($r, $c) = explode(',', $rc);
        if (isset($clientCache->cells[$r]) && isset($clientCache->cells[$r][$c])) {
            $taken[] = [
                'r' => $r,
                'c' => $c,
                'score' => $clientCache->client->revenue - $clientCache->cells[$r][$c]->pathCost,
                'output' => (string)($clientCache->cells[$r][$c]),
            ];
            $offices["$r,$c"] = true;
            unset($disconnectedClients[$clientCache->client->id]);
        }
    }
}

unset($caches);

foreach ($disconnectedClients as $clientId => $stocazzo) {
    $heavyCache = new PathMap($map, $clients[$clientId], $fileManager->inputName, true);
    $bestOffice = null;
    $bestOfficeScore = PHP_INT_MAX;
    foreach ($offices as $rc => $office) {
        list($r, $c) = explode(',', $rc);
        $cell = $heavyCache->getCell($r, $c);
        if ($cell && $cell->pathCost < $bestOfficeScore) {
            $bestOfficeScore = $cell->pathCost;
            $bestOffice = (string)$cell;
        }
    }
    if ($bestOffice) {
        $taken[] = [
            'score' => $heavyCache->client->revenue - $bestOfficeScore,
            'output' => $bestOffice,
        ];
    } else {
        echo "NOBONUS";
        die();
    }

    unset($heavyCache);
}

$score = $bonus;
$output = '';
foreach ($taken as $t) {
    $score += $t['score'];
    $output .= $t['output'] . "\n";
}
echo "\n\nScore: $score\n\n";
$fileManager->output($output);
