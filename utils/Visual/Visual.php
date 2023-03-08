<?php

namespace Utils\Visual;

use Utils\DirUtils;

abstract class Visual
{
    protected static string $outputDir = 'images';
    protected static array $saveIncrN = [];

    protected $image;

    public function __construct($rows, $columns)
    {
        $this->image = imagecreatetruecolor($columns, $rows);
    }

    public function saveIncr($name): void
    {
        self::$saveIncrN[$name]++;
        $this->save($name . '__' . str_pad(self::$saveIncrN[$name], 4, "0", STR_PAD_LEFT));
    }

    public function save($name): void
    {
        if (str_contains(self::$outputDir, "/")) {
            $path = explode("/", self::$outputDir);
            foreach ($path as $k => $v) {
                if ($k < count($path) - 1) {
                    $cpath = implode("/", array_slice($path, 0, $k + 1));
                    if (!is_dir($cpath))
                        mkdir($cpath);
                }
            }
        }
        $fileName = DirUtils::getScriptDir() . '/' . self::$outputDir . "/$name.png";
        $dirname = dirname($fileName);
        DirUtils::makeDirOrCreate($dirname);

        imagepng($this->image, $fileName);
        //imagedestroy($this->image);
    }

    public function allocateByString($color): false|int
    {
        $colorDec = $this->stringToColorDec($color);
        return imagecolorallocate($this->image, $colorDec[0], $colorDec[1], $colorDec[2]);
    }

    public function stringToColorDec($color): array
    {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        return [$r, $g, $b];
    }

    public abstract function setPixel($row, $col, $color);

    /**
     * @param $points
     * points [[0, 0],  [0, 2],  [2, 2],  [2, 0]]
     * @param $color
     */
    public function setBgPolygon($points, $color): void
    {
        $_points = [];
        foreach ($points as $p) {
            $_points[] = $p[0];
            $_points[] = $p[1];
        }
        imagefilledpolygon($this->image, $_points, count($points), $color);
    }
}
