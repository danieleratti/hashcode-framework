<?php

namespace Src\Utils\Visual;

use Src\Utils\DirUtils;

abstract class Visual
{
    protected static $outputDir = 'images';

    protected $image;

    public function __construct($rows, $columns)
    {
        $this->image = imagecreatetruecolor($columns, $rows);
    }

    public function save($name)
    {
        $fileName = DirUtils::getScriptDir() . '/' . self::$outputDir . "/$name.png";
        $dirname = dirname($fileName);
        DirUtils::makeDirOrCreate($dirname);

        imagepng($this->image, $fileName);
        //imagedestroy($this->image);
    }

    public function stringToColorDec($color)
    {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        return [$r, $g, $b];
    }

    public function allocateByString($color)
    {
        $colorDec = $this->stringToColorDec($color);
        return imagecolorallocate($this->image, $colorDec[0], $colorDec[1], $colorDec[2]);
    }

    public abstract function setPixel($row, $col, $color);
}
