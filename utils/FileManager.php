<?php

namespace Utils;

class FileManager
{
    private static $inputDir = 'input';
    private static $outputDir = 'output';
    private static $cacheDir = 'cache';

    public $inputName;
    public $fileContent;

    public function __construct($name)
    {
        $this->inputName = $this->getFileByStart($name);
        $this->fileContent = trim(file_get_contents(DirUtils::getScriptDir() . '/' . self::$inputDir . '/' . $this->inputName));
    }

    public function get()
    {
        return $this->fileContent;
    }

    private function getFileByStart($query)
    {
        foreach (self::listInputFiles() as $fileName) {
            if (substr($fileName, 0, strlen($query)) === $query)
                return $fileName;
        }
    }

    public function output($content, $extra = '')
    {
        $baseInputName = $this->getInputName();
        $scriptName = DirUtils::getScriptName();
        $this->write(DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $scriptName . '_' . $baseInputName . ($extra ? ('_' . $extra) : '') . '.txt', $content);
    }

    public function getInputName()
    {
        return basename($this->inputName, '.in');
    }

    public function write($fileName, $content)
    {
        $dirname = dirname($fileName);
        DirUtils::makeDirOrCreate($dirname);

        $fh = fopen($fileName, 'w');
        fwrite($fh, $content);
        fclose($fh);
    }

    private function append($fileName, $content)
    {
        $fh = fopen($fileName, 'w');
        fwrite($fh, $content);
        fclose($fh);
    }

    public static function listInputFiles()
    {
        $files = [];

        if ($handle = opendir(self::$inputDir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry[0] != '.')
                    $files[] = $entry;
            }

            closedir($handle);
        }

        return $files;
    }
}
