<?php

namespace Utils;

class Stopwatch
{
    public static $watches = [];
    private static $watches_tik = [];

    public static function reset()
    {
        self::$watches = [];
        self::$watches_tik = [];
    }

    public static function tik($name)
    {
        self::$watches_tik[$name] = microtime(true);
    }

    public static function tok($name)
    {
        if (!(@self::$watches[$name]))
            self::$watches[$name] = ['time' => 0, 'calls' => 0];
        self::$watches[$name]['calls']++;
        self::$watches[$name]['time'] += microtime(true) - self::$watches_tik[$name];
    }

    public static function print($name = null, $level = 0)
    {
        foreach (self::$watches as $n => $w) {
            if ($name === null || $n == $name) {
                Log::out("StopWatch[$n]: " . round($w['time'], 4) . "s (calls: ".$w['calls'].", time per call: ".round($w['time']/$w['calls']*1000)."ms)", $level, 'light_cyan');
            }
        }
    }
}
