<?php

namespace Src\Utils\Visual;

class VisualGradient extends Visual
{
    private $colors;

    public function __construct($rows, $columns, $start = '00ff00', $end = 'ff0000')
    {
        parent::__construct($rows, $columns);

        $precision = 255;

        $start = $this->stringToColorDec($start);
        $end = $this->stringToColorDec($end);

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
