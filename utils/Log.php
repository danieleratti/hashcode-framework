<?php

namespace Utils;

class Log
{
    public static $verbose = true;

    public static function verbose($verbose)
    {
        self::$verbose = $verbose;
    }

    public static function out($content, $level = 0, $textColor = null, $backgroundColor = null)
    {
        if (self::$verbose) {
            $padding = "";
            for ($i = 0; $i < $level; $i++)
                $padding .= "   ";

            $outputString = date("Y-m-d H:i:s") . " => " . $padding . $content;

            if ($textColor !== null || $backgroundColor !== null) {
                $colors = new ColoredString();
                echo $colors->getColoredString($outputString, $textColor, $backgroundColor) . "\n";
            } else
                echo $outputString . "\n";
        }
    }
}
