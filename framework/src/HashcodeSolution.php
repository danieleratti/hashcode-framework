<?php

namespace Src;

abstract class HashcodeSolution
{
    public $extraName = '';
    private $input;
    private $readerOffset = 0;

    public function __construct($input)
    {
        Model::resetRound();
        $this->input = explode("\n", $input);
    }

    public abstract function run();

    public function inputArray()
    {
        return $this->input;
    }

    public function inputNextChunk($length)
    {
        $value = array_slice($this->input, $this->readerOffset, $length);
        $this->readerOffset += $length;
        return $value;
    }

    public function inputNextLine(): string
    {
        return $this->inputNextChunk(1)[0];
    }

    public function inputSetReaderOffset($n)
    {
        $this->readerOffset = $n;
    }
}
