<?php

namespace Utils;

class DirUtils
{
    public static function getScriptDir()
    {
        return dirname($_SERVER["SCRIPT_FILENAME"]);
    }

    public static function getScriptName()
    {
        return basename($_SERVER["SCRIPT_FILENAME"], '.php');
    }

    public static function makeDirOrCreate($dirname)
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }
}
