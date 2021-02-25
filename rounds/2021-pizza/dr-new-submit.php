<?php

use Utils\Autoupload;
use Utils\Cerberus;
use Utils\Collection;
use Utils\Log;

/** @var Collection|Pizza[] $PIZZAS */
/** @var Collection|Ingredient[] $PIZZAS */
/** @var array $TEAMS */
/** @var \Utils\FileManager $fileManager */
/** @var array $PIZZAS_HASH */

require_once '../../bootstrap.php';

/* CONFIG */
$fileName = null;
$k1 = null;
Cerberus::runClient(['fileName' => 'c', 'k1' => 1.22]);
Autoupload::init();

include 'dr-reader.php';

// same algo of previous, different k1

$SCORE = "713907200";
$output = file_get_contents("output/dr-new-1_c_many_ingredients_k1_1.22--score_713907200.txt");
$fileManager->output($output, "k1_$k1--score_$SCORE");
Log::out("Uploading SCORE=$SCORE ($fileName)...");
Autoupload::submission($fileName, null, $output);
Log::out("Uploaded SCORE=$SCORE ($fileName)...");
