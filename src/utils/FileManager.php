<?php

namespace Src\Utils;

class FileManager
{
    private $inputDir = __DIR__ . '/../input';
    private $outputDir = __DIR__ . '/../output';

    private $inputName;
    private $fileContent;

    public function __construct($name)
    {
        $this->inputName = $this->getFileByStart($name);
        $this->fileContent = file_get_contents($this->inputDir . '/' . $this->inputName);
    }

    public function get()
    {
        return $this->fileContent;
    }

    private function getFileByStart($query)
    {
        foreach ($this->getInputFiles() as $fileName) {
            if (substr($fileName, 0, strlen($query)) === $query)
                return $fileName;
        }
    }

    private function getInputFiles()
    {
        $files = [];

        if ($handle = opendir($this->inputDir)) {
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
        $scriptName = basename($_SERVER["SCRIPT_FILENAME"], '.php');
        $this->write($this->outputDir . '/' . $scriptName . '_' . $baseInputName . '.txt', $content);
    }

    private function write($fileName, $content)
    {
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
