<?php

namespace Src\Utils\Visual;

class VisualGradient extends Visual
{
    private $colors;

    public function __construct($rows, $columns)
    {
        parent::__construct($rows, $columns);

        $precision = 200;
        $start = [255, 0, 0];
        $end = [0, 0, 255];
        $delta = [];
        for ($c = 0; $c < 3; $c++)
            $delta[$c] = ($end[$c] - $start[$c]) / $precision;

        $this->colors = [];
        $color = $start;
        for ($k = 0; $k < $precision; $k++) {
            $this->colors[] = imagecolorallocate($this->image, (int)$color[0], (int)$color[1], (int)$color[2]);

            for ($c = 0; $c < 3; $c++)
                $color[$c] = $color[$c] + $delta[$c];

        }
    }

    public function setPixel($row, $col, $gradient)
    {
        $colorId = (int)count($this->colors) * $gradient;
        imagesetpixel($this->image, $col, $row, $this->colors[$colorId]);
    }
}
