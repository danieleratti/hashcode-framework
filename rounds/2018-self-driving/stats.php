<?php

$fileName = 'e';

include 'reader.php';

$distances = $rides->sum('distance');
$bonus = $B * $N;
$total = $bonus + $distances;

echo "bonus: $bonus, distances: $distances, total: $total";
