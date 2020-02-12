<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

$fileName = 'f';

/** @var TargetFile[] $targets */
/** @var File[] $files */

require_once 'reader.php';

/** @var Graph $graph */
$graph = new Graph();
/** @var Vertex[] $vertexes */
$vertexes = [];

function addVertexes($file, &$graph, &$vertexes)
{
    /** @var File $file */
    /** @var Graph $graph */
    /** @var Vertex[] $vertexes */
    if (isset($vertexes[$file->id])) return;
    $vertexes[$file->id] = $graph->createVertex($file->id);
    foreach ($file->dependencies as $d) {
        addVertexes($d, $graph, $vertexes);
    }
}

$selectedTarget = ['c65l', 'cyq6', 'cz26', 'c9a6', 'czu6'];
array_splice($targets, 20);
foreach ($targets as $target) {
    //if (in_array($target->file->id, $selectedTarget)) continue;
    addVertexes($target->file, $graph, $vertexes);
}


foreach ($files as $file) {
    foreach ($file->dependencies as $dependency) {
        if (isset($vertexes[$dependency->id]) && isset($vertexes[$file->id])) {
            $vertexes[$dependency->id]->createEdgeTo($vertexes[$file->id]);
        }
    }
}


$graphviz = new GraphViz();
$fileData = $graphviz->createImageHtml($graph);
file_put_contents(__DIR__ . '/visual/graph_' . $fileName . '.html', $fileData);
