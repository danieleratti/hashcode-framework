<?php

namespace Utils;

use Src\Utils\DirUtils;

Class Cache
{
    protected $filename;
    protected $dir;
    public static $prefix;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->dir = DirUtils::getScriptDir() . '/caches';
    }

    private function getFilename()
    {
        return $this->dir . '/' . (self::$prefix ? (self::$prefix . '_') : '') . $this->filename . '.json';
    }

    public function save($payload)
    {
        $fh = fopen($this->getFilename(), 'w');
        fwrite($fh, json_encode($payload));
        fclose($fh);
    }

    public function get()
    {
        $f = @file_get_contents($this->getFilename());
        if (@$f)
            return json_decode($f, true);
        return false;
    }
}
