<?php

namespace Utils\Visual;

class VisualGradient extends Visual
{
    private $colors;

    public function __construct($rows, $columns)
    {
        parent::__construct($rows, $columns);

        $this->colors = [];

        $r = 0;
        $g = 254;

        for ($k = 0; $k < 256; $k++) {
            $this->colors[] = imagecolorallocate($this->image, $r, $g, 0);

            if ($k < 127)
                $r += 2;
            if ($k > 127)
                $g -= 2;

        }
    }

    public function setPixel($row, $col, $gradient)
    {
        $colorId = (int)count($this->colors) * $gradient;
        imagesetpixel($this->image, $col, $row, $this->colors[$colorId]);
    }

    public function setCustomPixel($row, $col, $r, $g, $b)
    {
        imagesetpixel($this->image, $col, $row, imagecolorallocate($this->image, $r, $g, $b));
    }
}
