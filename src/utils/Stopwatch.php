<?php

namespace Src\Utils;

class Stopwatch
{
    private $tik;
    private $times;
    private $label;

    function __construct($label)
    {
        $this->times = [];
        $this->label = $label;
    }

    public function tik()
    {
        $this->tik = microtime(true);
    }

    public function tok($print = false)
    {
        $this->times[] = microtime(true) - $this->tik;
        $this->tik = null;

        if ($print)
            $this->printTime();
    }

    public function printTime()
    {
        $r = 4;
        $average = round(array_sum($this->times) / count($this->times), $r);
        $last = round(end($this->times), $r);
        $max = round(max($this->times), $r);
        $min = round(min($this->times), $r);

        echo $this->label . ": {$last}s (μ: $average, max: $max, min: $min)\n";
    }
}
