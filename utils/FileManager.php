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
        $filePath = DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $scriptName . '_' . $baseInputName . ($extra ? ('_' . $extra) : '') . '.txt';
        $this->write($filePath, $content);
        return $filePath;
    }

    public function outputV2($content, $extra = '')
    {
        $baseInputName = $this->getInputName();
        $scriptName = DirUtils::getScriptName();
        $basePath = DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $baseInputName . '/' . $scriptName . ($extra ? ('_' . $extra) : '');
        $outputPath = $basePath . '.txt';
        $sourcePath = $basePath . '.php.txt'; // in order to exclude from searches
        $this->write($outputPath, $content);
        if (Autoupload::$scriptContent)
            $this->write($sourcePath, Autoupload::$scriptContent);
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
