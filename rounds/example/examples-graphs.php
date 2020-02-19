<?php

use Utils\Graph;

require_once '../../bootstrap.php';

$graph = new Graph('test');

$vertexes = [['id' => 'a'], ['id' => 'b'], ['id' => 'c', 'label' => 'CCC', 'color' => 'red', 'shape' => 'box']];
$edges = [['from' => 'a', 'to' => 'b'], ['from' => 'b', 'to' => 'c', 'color' => 'blue']];

$graph->plotGraph($vertexes, $edges);
