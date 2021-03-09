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
        foreach (DirUtils::listFiles(DirUtils::getScriptDir() . '/' . self::$inputDir) as $fileName) {
            if (substr($fileName, 0, strlen($query)) === $query)
                return $fileName;
        }
    }

    public function output($content, $extra = '')
    {
        $baseInputName = $this->getInputName();
        $scriptName = DirUtils::getScriptName();
        $filePath = DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $scriptName . '_' . $baseInputName . ($extra ? ('_' . $extra) : '') . '.txt';
        File::write($filePath, $content);
        return $filePath;
    }

    public function outputV2($content, $extra = '')
    {
        $baseInputName = $this->getInputName();
        $scriptName = DirUtils::getScriptName();
        $basePath = DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $baseInputName . '/' . $scriptName . ($extra ? ('_' . $extra) : '');
        $outputPath = $basePath . '.txt';
        $sourcePath = $basePath . '.php.txt'; // in order to exclude from searches
        File::write($outputPath, $content);
        if (Autoupload::$scriptContent)
            File::write($sourcePath, Autoupload::$scriptContent);
    }

    public function getInputName()
    {
        return basename($this->inputName, '.in');
    }
}
