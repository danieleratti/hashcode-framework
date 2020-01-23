<?php

namespace Utils;

class FileManager
{
    private static $inputDir = 'input';
    private static $outputDir = 'output';

    private $inputName;
    private $fileContent;

    public function __construct($name)
    {
        $this->inputName = $this->getFileByStart($name);
        $this->fileContent = file_get_contents(DirUtils::getScriptDir() . '/' . self::$inputDir . '/' . $this->inputName);
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

    public function output($content)
    {
        $baseInputName = basename($this->inputName, '.in');
        $scriptName = DirUtils::getScriptName();
        $this->write(DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $scriptName . '_' . $baseInputName . '.txt', $content);
    }

    private function write($fileName, $content)
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
}
