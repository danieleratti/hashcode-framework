<?php

use Utils\Collection;

$fileName = 'f';

include 'reader.php';

/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */

//echo $countDays . "\n";
$avg = $libraries->avg('shipsPerDay');
echo $avg . "\n";
//echo ($countDays / $avg) . "\n";

$output = '';

//$fileManager->output(implode("\n", $output));
