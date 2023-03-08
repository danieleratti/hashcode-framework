<?php

namespace Utils;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

class FileManager
{
    private static string $inputDir = 'input';
    private static string $outputDir = 'output';
    private static string $cacheDir = 'cache';

    public ?string $inputName;
    public string $fileContent;

    public function __construct(string $name)
    {
        $this->inputName = $this->getFileByStart($name);
        $this->fileContent = trim(file_get_contents(DirUtils::getScriptDir() . '/' . self::$inputDir . '/' . $this->inputName));
    }

    /**
     * @throws FileNotFoundException
     */
    private function getFileByStart($query): ?string
    {
        foreach (DirUtils::listFiles(DirUtils::getScriptDir() . '/' . self::$inputDir) as $fileName) {
            if (str_starts_with($fileName, $query))
                return $fileName;
        }

        throw new FileNotFoundException("File starting with '$query' not found.");
    }

    public function get(): string
    {
        return $this->fileContent;
    }

    public function output($content, $extra = ''): string
    {
        $baseInputName = $this->getInputName();
        $scriptName = DirUtils::getScriptName();
        $filePath = DirUtils::getScriptDir() . '/' . self::$outputDir . '/' . $scriptName . '_' . $baseInputName . ($extra ? ('_' . $extra) : '') . '.txt';
        File::write($filePath, $content);
        return $filePath;
    }

    public function getInputName(): string
    {
        return basename($this->inputName, '.in');
    }

    public function outputV2($content, $extra = ''): void
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
}
