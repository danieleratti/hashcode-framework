<?php

/*
 * Documentation: https://plot.ly/javascript/
 *
 * Usage: right-click on file -> open in browser
*/

namespace Utils;

Class Chart
{
    protected $filename;
    public static $prefix;
    public static $outputDir = 'charts';

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

    private function generatePayload($data, $layout = [])
    {
        return "
        <html>
            <head>
                <script src='https://cdn.plot.ly/plotly-latest.min.js'></script>
            </head>
            <body>
                <div id='chartDiv'></div>
                <script>
                    var data = " . json_encode($data) . ";
                    var layout = " . json_encode($layout) . ";
                    Plotly.newPlot('chartDiv', data, layout);
                </script>
            </body>
        </html>";
    }

    /* Plot Charts */
    public function plotCustom($data, $layout = [])
    {
        return $this->save($this->generatePayload($data, $layout));
    }

    /*
     * @param $line
     * eg. [1,2,3,4,3,2,1]
     */
    public function plotLineY($line)
    {
        return $this->plotCustom([
            [
                'y' => $line,
                'type' => 'scatter'
            ]
        ]);
    }

    /*
     * @param $lines
     * eg. [ ['name': 'series1', 'line':[1,2,3,4,3,2,1]], ['name': 'series2', 'line':[2,3,4,3,2,1,2]] ]
     */
    public function plotMultiLineY($lines)
    {
        $customAxis = 2;
        $plot = [];
        $layout = [];

        foreach ($lines as $line) {
            $item = [
                'y' => $line['line'],
                'type' => 'scatter',
                'name' => $line['name']
            ];
            if ($line['custom_axis']) {
                $item['yaxis'] = 'y' . $customAxis;
                $layout = [
                    'yaxis' . $customAxis => [
                        'title' => $line['name'],
                        'overlaying' => 'y',
                        'side' => $line['side'] ?: 'left',
                    ]
                ];
                $customAxis++;
            }
            $plot[] = $item;
        }

        return $this->plotCustom($plot, $layout);
    }

    /*
     * @param $lines
     * eg. [ ['name': 'series1', 'line':[['a', 1],['b', 2],['c', 3]]], ['name': 'series2', 'line':[['a', 2],['b', 3],['c', 4]]] ]
     */
    public function plotMultiLineXY($lines)
    {
        $customAxis = 2;
        $plot = [];
        $layout = [];

        foreach ($lines as $line) {
            $item = [
                'x' => array_column($line['line'], 0),
                'y' => array_column($line['line'], 1),
                'type' => 'scatter',
                'name' => $line['name']
            ];
            if ($line['custom_axis']) {
                $item['yaxis'] = 'y' . $line['custom_axis'];
                $layout['yaxis' . $line['custom_axis']] = [
                    'title' => $line['name'],
                    'overlaying' => 'y',
                    'side' => $line['side'] ?: 'left',
                ];
                $customAxis++;
            }
            $plot[] = $item;
        }

        return $this->plotCustom($plot, $layout);
    }

    /*
     * @param $line
     * eg. [[1,1], [2,2], [3,3]]
     */
    public function plotLineXY($line)
    {
        $lineX = [];
        $lineY = [];
        foreach ($line as $l) {
            $lineX[] = $l[0];
            $lineY[] = $l[1];
        }
        return $this->plotCustom([
            [
                'x' => $lineX,
                'y' => $lineY,
                'type' => 'scatter'
            ]
        ]);
    }

    /*
     * @param $points
     * eg. [[1,1,'lab1'], [2,2], [3,3]]
     */
    public function plotPoints($points)
    {
        $pointsX = [];
        $pointsY = [];
        $texts = [];
        foreach ($points as $p) {
            $pointsX[] = $p[0];
            $pointsY[] = $p[1];
            $texts[] = $p[2];
        }
        return $this->plotCustom([
            [
                'x' => $pointsX,
                'y' => $pointsY,
                'text' => $texts,
                'type' => 'pointcloud'
            ]
        ]);
    }

    /*
     * @param $points
     * eg. [[1,1,1], [2,2,2], [3,3,3]]
     */
    public function plotPoints3D($points)
    {
        $pointsX = [];
        $pointsY = [];
        $pointsZ = [];
        foreach ($points as $p) {
            $pointsX[] = $p[0];
            $pointsY[] = $p[1];
            $pointsZ[] = $p[2];
        }
        return $this->plotCustom([
            [
                'x' => $pointsX,
                'y' => $pointsY,
                'z' => $pointsY,
                'mode' => 'markers',
                'marker' => [
                    'color' => 'blue',
                    'size' => 10,
                    'symbol' => 'circle'
                ],
                'type' => 'scatter3d'
            ]
        ], [
            'l' => 0,
            'r' => 0,
            'b' => 0,
            't' => 0,
            'height' => 768,
        ]);
    }

    /*
     * @param $bubbles
     * [ [x, y, size, color, text], ... ]
     * eg. [[1, 1, 1, red, '1 small red'], [2, 2, 2, blue, '2 medium blue'], [3, 3, 3, green, '3 big green']]
     */
    public function plotBubbles($bubbles)
    {
        $xs = [];
        $ys = [];
        $sizes = [];
        $colors = [];
        $texts = [];
        foreach ($bubbles as $b) {
            $xs[] = $b[0];
            $ys[] = $b[1];
            $sizes[] = $b[2] * 10 ?: 10;
            $colors[] = $b[3] ?: 'blue';
            $texts[] = $b[4] ?: ('x=' . $b[0] . ' y=' . $b[1]);
        }

        $layout = [
            'title' => 'Bubble Chart',
            'showlegend' => false,
            'height' => 768,
        ];

        return $this->plotCustom([
            [
                'x' => $xs,
                'y' => $ys,
                'text' => $texts,
                'mode' => 'markers',
                'marker' => [
                    'color' => $colors,
                    'size' => $sizes
                ]
            ]
        ], $layout);
    }

    /*
     * @param $histogram
     * [ xi, ... ]
     * eg. [1, 2, 3, 3, 3, 2]
     */
    public function plotHistogram($histogram)
    {
        return $this->plotCustom([
            [
                'x' => $histogram,
                'type' => 'histogram'
            ]
        ]);
    }

    /*
     * @param $histogram
     * [ [xi, yi], ... ]
     * eg. [[1,1], [2,2], [3,3]]
     */
    public function plotHistogram2D($histogram)
    {
        $histogramX = [];
        $histogramY = [];
        foreach ($histogram as $l) {
            $histogramX[] = $l[0];
            $histogramY[] = $l[1];
        }
        return $this->plotCustom([
            [
                'x' => $histogramX,
                'y' => $histogramY,
                'type' => 'histogram2dcontour'
            ]
        ]);
    }
}
