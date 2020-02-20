<?php

use Utils\Collection;

$fileName = 'a';

include 'reader.php';

/**
 * @var integer $countBooks
 * @var integer $countLibraries
 * @var integer $countDays
 * @var Collection $books
 * @var Collection $libraries
 */

echo $libraries->avg('signUpDuration');

$output = '';

//$fileManager->output(implode("\n", $output));
