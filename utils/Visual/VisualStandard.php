<?php

namespace Utils\Visual;

use ReflectionClass;

Class VisualStandard extends Visual
{
    protected $colors;

    public function __construct($rows, $columns)
    {
        parent::__construct($rows, $columns);

        $reflectedColors = new ReflectionClass(Colors::class);

        $this->colors = [];
        foreach ($reflectedColors->getConstants() as $name => $color) {
            $this->colors[$color] = $this->allocateByString($color);
        }

        $this->setBg(Colors::white);
    }

    public function setPixel($row, $col, $color)
    {
        imagesetpixel($this->image, $col, $row, $this->colors[$color]);
    }

    public function drawEllipse($row, $col, $size, $color)
    {
        imagefilledellipse($this->image, $col, $row, $size, $size, $this->colors[$color]);
    }

    public function drawLine($r1, $c1, $r2, $c2, $color) {
        imageline($this->image, $c1, $r1, $c2, $r2, $this->colors[$color]);
    }

    public function setBg($color)
    {
        imagefill($this->image, 0, 0, $this->colors[$color]);
    }

    public function setLine($r1, $c1, $r2, $c2, $color)
    {
        // x => c | y => r
        imageline($this->image, $c1, $r1, $c2, $r2, $this->colors[$color]);
    }
}
