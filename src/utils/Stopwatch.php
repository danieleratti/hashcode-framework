<?php

namespace Src\Utils;

class Stopwatch
{
    private $tik;
    private $label;

    private $last;
    private $max;
    private $min;
    private $sum;
    private $count;

    function __construct($label)
    {
        $this->label = $label;
    }

    public function tik()
    {
        $this->tik = microtime(true);
    }

    public function tok($print = true)
    {
        $this->last = microtime(true) - $this->tik;
        $this->sum += $this->last;
        $this->count++;
        $this->min = min($this->min, $this->last);
        $this->max = max($this->max, $this->last);

        if ($print)
            $this->printTime();
    }

    public function printTime()
    {
        $r = 4;

        $average = round($this->sum / $this->count, $r);
        $last = round($this->last, $r);
        $max = round($this->max, $r);
        $min = round($this->min, $r);

        echo $this->label . ": {$last}s (μ: $average, max: $max, min: $min)\n";
    }
}
