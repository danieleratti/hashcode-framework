<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

$fileName = 'c';

require_once 'reader.php';

/** @var Graph $graph */
$graph = new Graph();
/** @var Vertex[] $vertexes */
$vertexes = [];

foreach ($files as $file) {
    $vertexes[$file->id] = $graph->createVertex($file->id);
}

foreach ($files as $file) {
    foreach ($file->dependencies as $dependency) {
        $vertexes[$dependency->id]->createEdgeTo($vertexes[$file->id]);
    }
}

$graphviz = new GraphViz();
$fileData = $graphviz->createImageHtml($graph);
file_put_contents(__DIR__ . '/visual/graph_' . $fileName . '.html', $fileData);
