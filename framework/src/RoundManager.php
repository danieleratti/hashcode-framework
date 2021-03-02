<?php

namespace Src;

use Utils\DirUtils;
use Utils\File;

class RoundManager
{
    private $toRunInputs = [];
    private $autoUpload = false;

    public function __construct()
    {
        DirUtils::makeDirOrCreate($this->getScriptDir() . '/' . Config::OUTPUT_DIR);
    }

    private function getScriptDir()
    {
        return DirUtils::getScriptDir();
    }

    private function getAvailableInputs()
    {
        $inputs = DirUtils::listFiles($this->getScriptDir() . '/' . Config::INPUT_DIR);

        if (!count($inputs))
            die('Inputs not found!');

        sort($inputs);
        $mapped = [];
        foreach ($inputs as $input) {
            $mapped[substr($input, 0, 1)] = $input;
        }
        return $mapped;
    }

    /**
     * @param string[] $inputs
     * @return $this
     */
    public function toRunInputs(array $inputs)
    {
        $this->toRunInputs = $inputs;
        return $this;
    }

    /**
     * @param bool $auto
     * @return $this
     */
    public function autoUpload($auto = true)
    {
        $this->autoUpload = true;
        return $this;
    }

    public function run($solver)
    {
        $inputs = $this->getAvailableInputs();

        $toRun = $this->toRunInputs;
        if (!count($toRun))
            $toRun = array_keys($inputs);

        foreach ($toRun as $fileKey) {
            $inputName = $inputs[$fileKey];
            $fileContent = trim(file_get_contents($this->getScriptDir() . '/' . Config::INPUT_DIR . '/' . $inputName));
            echo "FILE $inputName\n";

            /** @var HashcodeSolution $solution */
            $solution = new $solver($fileContent);
            $output = $solution->run();
            $this->saveOutput($output, $inputName, $solution->extraName);

            echo "\n\n\n";
        }
    }

    private function saveOutput($content, $baseInputName, $extraName = '')
    {
        $scriptName = DirUtils::getScriptName();
        $basePath = DirUtils::getScriptDir() . '/' . Config::OUTPUT_DIR . '/' . $baseInputName . '/' . $scriptName . ($extraName ? ('_' . $extraName) : '');
        $outputPath = $basePath . '.txt';
        File::write($outputPath, $content);
    }
}
