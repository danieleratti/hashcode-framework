<?php

namespace Utils;

class Stopwatch
{
    public static array $watches = [];
    private static array $watches_tik = [];

    public static function reset(): void
    {
        self::$watches = [];
        self::$watches_tik = [];
    }

    public static function tik($name): void
    {
        self::$watches_tik[$name] = microtime(true);
    }

    public static function tok($name): void
    {
        if (!(@self::$watches[$name]))
            self::$watches[$name] = ['time' => 0, 'calls' => 0];
        self::$watches[$name]['calls']++;
        self::$watches[$name]['time'] += microtime(true) - self::$watches_tik[$name];
    }

    public static function print($name = null, $level = 0): void
    {
        foreach (self::$watches as $n => $w) {
            if ($name === null || $n == $name) {
                Log::out("StopWatch[$n]: " . round($w['time'], 4) . "s (calls: " . $w['calls'] . ", time per call: " . round($w['time'] / $w['calls'] * 1000) . "ms)", $level, 'light_cyan');
            }
        }
    }
}
