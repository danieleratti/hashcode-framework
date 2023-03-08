<?php

namespace Utils;

use JetBrains\PhpStorm\NoReturn;

class Log
{
    public static bool $verbose = true;
    public static bool $dates = true;

    public static function verbose($verbose): void
    {
        self::$verbose = $verbose;
    }

    #[NoReturn] public static function error($content): void
    {
        Log::out("ERROR: " . $content, 0, 'red');
        die();
    }

    public static function out($content, $level = 0, $textColor = null, $backgroundColor = null): void
    {
        if (self::$verbose) {
            $padding = str_repeat("   ", $level);

            if (self::$dates)
                $outputString = date("Y-m-d H:i:s") . " => ";
            else
                $outputString = '';

            $outputString .= $padding . $content;

            if ($textColor !== null || $backgroundColor !== null) {
                $colors = new ColoredString();
                echo $colors->getColoredString($outputString, $textColor, $backgroundColor) . "\n";
            } else
                echo $outputString . "\n";
        }
    }
}
