<?php

namespace Utils;

class File
{
    public static function write($fileName, $content): void
    {
        $dirname = dirname($fileName);
        DirUtils::makeDirOrCreate($dirname);

        $fh = fopen($fileName, 'w');
        fwrite($fh, $content);
        fclose($fh);
    }

    public static function append($fileName, $content): void
    {
        $fh = fopen($fileName, 'w');
        fwrite($fh, $content);
        fclose($fh);
    }
}
