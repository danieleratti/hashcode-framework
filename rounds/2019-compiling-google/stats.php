<?php

$fileName = 'a';

require_once 'reader.php';

$files = collect($files);
echo "Numero di file: $filesCount\n";
echo "Numero di target: $targetCount\n";
echo "Numero di server: $serversCount\n\n";

$max = $files->max('compilingTime');
$min = $files->min('compilingTime');
$avg = $files->avg('compilingTime');
echo "Tempo compilazione: max $max min $min avg $avg\n\n";

$max = $files->max('replicationTime');
$min = $files->min('replicationTime');
$avg = $files->avg('replicationTime');
echo "Tempo replicazione: max $max min $min avg $avg\n\n";

$max = $targets->max('deadline');
$min = $targets->min('deadline');
$avg = $targets->avg('deadline');
echo "Deadline: max $max min $min avg $avg\n\n";
