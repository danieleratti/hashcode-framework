<?php

namespace Src;

use Src\Utils\File;

abstract class HashCode
{
    public $workingDir, $inputName, $inputContent;

    public function __construct($inputName, $dir = false)
    {
        if (!$dir)
            $dir = $_SERVER['PWD'];
        $this->workingDir = $dir;
        $this->inputName = $inputName;
        $this->inputContent = file_get_contents($dir . '/input/' . $inputName . '.in');

        try {
            $this->readInput();
            $this->solve();
        } catch (\Exception $e) {
            echo "### EXCEPTION " . $e->getMessage() . " ###\n";
            echo $e->getTraceAsString();
        }
    }

    public function output($content, $outputName = false, $history = false)
    {
        $fileName = $this->inputName;
        if ($outputName)
            $fileName .= '-' . $outputName;
        if ($history)
            $fileName .= '-' . date('H-i-s');
        File::write($this->workingDir . '/output/' . $fileName . '.txt', $content);
    }

    /* TO BE IMPLEMENTED inside the reader */
    public function readInput()
    {
        /* read the $this->inputContent file */
    }

    /* TO BE IMPLEMENTED inside the solution */
    public function solve()
    {
        /* solve the problem */
    }
}
