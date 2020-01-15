<?php

require_once __DIR__ . '/File.php';
require_once __DIR__ . '/Utils.php';

/*
 *
 * */

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

        $this->readInput();
        $this->solve();
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

    /* to be implemented */
    public function readInput()
    {
        /* read the $this->inputContent file */
    }
}
