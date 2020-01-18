<?php

namespace Src\Utils;

Class Visual
{
    private static $outputDir = __DIR__ . '/../images';

    private $image;
    private $baseColors;
    private $gradientColors;

    public function __construct($rows, $columns)
    {
        $this->image = imagecreatetruecolor($columns, $rows);

        $colors = [
            'purple' => [0x36, 0x00, 0x43],
            'lightblue' => [0x53, 0x92, 0xA4],
            'water' => [0x20, 0x60, 0x71],
            'yellow' => [0xFF, 0xFF, 0x00],
            'red' => [0xFF, 0x00, 0x00],
            'green' => [0x00, 0xFF, 0x00],
            'blue' => [0x33, 0x35, 0x6C],
            'white' => [0xFF, 0xFF, 0xFF],
            'black' => [0x00, 0x00, 0x00],
        ];

        $this->baseColors = [];
        foreach ($colors as $key => $color) {
            $this->baseColors[$key] = imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
        }

        $precision = 200;
        $start = [255, 0, 0];
        $end = [0, 0, 255];
        $delta = [];
        for ($c = 0; $c < 3; $c++)
            $delta[$c] = ($end[$c] - $start[$c]) / $precision;

        $this->gradientColors = [];
        $color = $start;
        for ($k = 0; $k < $precision; $k++) {
            $this->gradientColors[] = imagecolorallocate($this->image, (int)$color[0], (int)$color[1], (int)$color[2]);

            for ($c = 0; $c < 3; $c++)
                $color[$c] = $color[$c] + $delta[$c];

        }

        $this->setBg('white');
    }

    public function setPixel($row, $col, $color)
    {
        imagesetpixel($this->image, $col, $row, $this->getAllocatedColor($color));
    }

    public function setGradient($row, $col, $gradient)
    {
        $colorId = (int)count($this->gradientColors) * $gradient;
        imagesetpixel($this->image, $col, $row, $this->gradientColors[$colorId]);
    }

    public function setBg($color)
    {
        imagefill($this->image, 0, 0, $this->getAllocatedColor($color));
    }

    public function save($name)
    {
        $fileName = self::$outputDir . "/$name.png";
        $dirname = dirname($fileName);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        imagepng($this->image, $fileName);
        //imagedestroy($this->image);
    }

    private function getAllocatedColor($color)
    {
        if (is_int($color))
            $color = array_keys($this->baseColors)[$color];

        return $this->baseColors[$color];
    }
}
