<?php

namespace Utils;

class Log
{
    public static $verbose = true;

    public static function verbose($verbose)
    {
        self::$verbose = $verbose;
    }

    public static function out($content, $level = 0)
    {
        if (self::$verbose) {
            $padding = "";
            for($i=0;$i<$level;$i++)
                $padding .= "   ";
            echo date("Y-m-d H:i:s") . " => " . $padding . $content . "\n";
        }
    }
}
