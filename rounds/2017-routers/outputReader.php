<?php

/**
 * @var integer $gridRows
 * @var integer $gridCols
 * @var integer $routerRange
 * @var integer $backboneCosts
 * @var integer $routerCosts
 * @var integer $budget
 * @var integer $backboneRow
 * @var integer $backboneCol
 * @var integer[][] $gridArray
 */

$fileName = 'a';
$outputName = 'sol1_b_charleston_road.txt';

include 'reader.php';

function isAdiacent($r1, $c1, $r2, $c2)
{
  return (abs($r1 - $r2) <= 1) && (abs($c1 - $c2) <= 1);
}

$content = trim(file_get_contents(__DIR__ . '/output/' . $outputName));
if (!$content) {
  die("No output file");
}
$rows = explode("\n", $content);

// Controlli
$backboneLength = $rows[0];
array_shift($rows);

$backbones = [];
for ($i = 0; $i < $backboneLength; $i++) {
  $backbones[] = explode(' ', $rows[0]);
  array_shift($rows);
}

$routersNumber = $rows[0];
array_shift($rows);

$routers = [];
for ($i = 0; $i < $routersNumber; $i++) {
  $routers[] = explode(' ', $rows[$i]);
}

for ($i = 0; $i < $backboneLength; $i++) {
  $r = $backbones[$i][0];
  $c = $backbones[$i][1];
  if (isAdiacent($r, $c, $backboneRow, $backboneCol))
    continue;
  $foundAdiacent = false;
  for ($j = 0; $j < $i; $j++) {
    $r2 = $backbones[$j][0];
    $c2 = $backbones[$j][1];
    if (isAdiacent($r, $c, $r2, $c2)) {
      $foundAdiacent = true;
      break;
    }
  }
  if (!$foundAdiacent) {
    die("Backbone non valida");
  }
}

for ($i = 0; $i < $routersNumber; $i++) {
  $r = $routers[$i][0];
  $c = $routers[$i][1];

  $found = false;
  for ($j = 0; $j < $backboneLength; $j++) {
    $r2 = $backbones[$j][0];
    $c2 = $backbones[$j][1];
    if ($r == $r2 && $c == $c2) {
      $found = true;
      break;
    }
  }

  if (!$found) {
    die("Il router non è connesso alla backbone");
  }

  if ($gridArray[$r][$c] == '#') {
    die("Il router è sul muro");
  }
}

$totalBackboneCost = $backboneLength * $backboneCosts;
$totalRoutersCost = $routersNumber * $routerCosts;
$totalCost = $totalBackboneCost + $totalRoutersCost;

if ($totalCost > $budget) {
  die("Il budget non è stato rispettato");
}

// Calcolo punteggio
$boolGrid = [];
for ($i = 0; $i < $gridRows; $i++) {
  for ($j = 0; $j < $gridCols; $j++) {
    $boolGrid[$i][$j] = false;
  }
}

for ($i = 0; $i < $routersNumber; $i++) {
  $r = $routers[$i][0];
  $c = $routers[$i][1];
  $boolGrid = addWifi($boolGrid, $gridArray, $r, $c, $routerRange);
}

$t = 0;
for ($i = 0; $i < $gridRows; $i++) {
  for ($j = 0; $j < $gridCols; $j++) {
    if ($boolGrid[$i][$j]) {
      $t++;
    }
  }
}

$score = 1000 * $t + ($budget - $totalCost);
echo "Lo score è " . $score;
