<?php

namespace Src\Utils\Visual;

abstract class Visual
{
    protected static $outputDir = __DIR__ . '/../../images';

    protected $image;

    public function __construct($rows, $columns)
    {
        $this->image = imagecreatetruecolor($columns, $rows);
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

    public abstract function setPixel($row, $col, $color);
}
