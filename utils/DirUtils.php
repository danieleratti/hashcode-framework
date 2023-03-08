<?php

namespace Utils;

class DirUtils
{
    public static function getScriptDir(): string
    {
        return dirname($_SERVER["SCRIPT_FILENAME"]);
    }

    public static function getScriptName(): string
    {
        return basename($_SERVER["SCRIPT_FILENAME"], '.php');
    }

    public static function makeDirOrCreate($dirname): void
    {
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }
    }

    public static function listFiles($dir): array
    {
        $files = [];

        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry[0] != '.')
                    $files[] = $entry;
            }

            closedir($handle);
        }

        return $files;
    }
}
