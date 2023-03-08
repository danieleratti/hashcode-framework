<?php

namespace Utils;

use Utils\DirUtils;

class Cache
{
    public static string $prefix;
    protected string $filename;
    protected string $dir;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->dir = DirUtils::getScriptDir() . '/caches';
    }

    public function save($payload): void
    {
        $fh = fopen($this->getFilename(), 'w');
        fwrite($fh, json_encode($payload));
        fclose($fh);
    }

    private function getFilename(): string
    {
        return $this->dir . '/' . (self::$prefix ? (self::$prefix . '_') : '') . $this->filename . '.json';
    }

    public function get()
    {
        $f = @file_get_contents($this->getFilename());
        if (@$f)
            return json_decode($f, true);
        return false;
    }
}
