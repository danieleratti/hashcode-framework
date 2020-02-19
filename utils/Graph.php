<?php

/*
 * Documentation: https://github.com/graphp/graphviz
 * Install: brew install graphviz
 * Usage: right-click on file -> open in browser
*/

namespace Utils;

use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;

Class Graph
{
    protected $filename;
    public static $prefix;
    public static $outputDir = 'graphs';

    public function __construct($fileName)
    {
        $fileName = DirUtils::getScriptDir() . '/' . self::$outputDir . "/$fileName.html";
        $this->filename = $fileName;
        $dirname = dirname($fileName);
        DirUtils::makeDirOrCreate($dirname);
    }

    private function save($payload)
    {
        $fh = fopen($this->filename, 'w');
        fwrite($fh, $payload);
        fclose($fh);
        return $this->filename;
    }

    /*
     * @param $vertexes [ ['id' => 'a'], ['id' => 'b'], ['id' => 'c', 'label' => 'CCC', 'color' => 'red', 'shape' => 'box'] ]
     *  id*
     *  color
     *  shape
     *  label (different from id)
     *  label_html
     *
     * @param $edges [ ['from' => 'a', 'to' => 'b'], ['from' => 'b', 'to' => 'c', 'color' => 'gray'] ]
     *  from*
     *  to*
     *  color
     *
     * Shapes: box, polygon, oval, circle, diamond, star, none
     */
    public function plotGraph($vertexes, $edges)
    {
        $graph = new \Fhaculty\Graph\Graph();
        /** @var Vertex[] $vertexes */
        $_vertexes = [];

        foreach ($vertexes as $v) {
            $vertex = $graph->createVertex($v['id']);
            if($v['color']) $vertex->setAttribute('graphviz.color', $v['color']);
            if($v['shape']) $vertex->setAttribute('graphviz.shape', $v['shape']);
            if($v['label']) $vertex->setAttribute('graphviz.label', $v['label']);
            if($v['label_html']) $vertex->setAttribute('graphviz.label_html', $v['label_html']);
            $_vertexes[$v['id']] = $vertex;
        }

        foreach ($edges as $edge) {
            /** @var Directed $_edge */
            $_edge = $_vertexes[$edge['from']]->createEdgeTo($_vertexes[$edge['to']]);
            if($edge['color']) $_edge->setAttribute('graphviz.color', $edge['color']);
        }

        $graphviz = new GraphViz();
        $fileData = $graphviz->createImageHtml($graph);
        $this->save($fileData);
    }
}
