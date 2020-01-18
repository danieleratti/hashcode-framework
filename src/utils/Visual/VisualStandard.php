<?php

namespace Src\Utils\Visual;

use ReflectionClass;

Class VisualStandard extends Visual
{
    private $colors;

    public function __construct($rows, $columns)
    {
        parent::__construct($rows, $columns);

        $reflectedColors = new ReflectionClass(Colors::class);

        $this->colors = [];
        foreach ($reflectedColors->getConstants() as $name => $color) {
            $this->colors[$color] = $this->allocateByString($color);
        }

        $this->setBg('white');
    }

    public function setPixel($row, $col, $color)
    {
        imagesetpixel($this->image, $col, $row, $this->colors[$color]);
    }

    public function setBg($color)
    {
        imagefill($this->image, 0, 0, $this->colors[$color]);
    }
}
