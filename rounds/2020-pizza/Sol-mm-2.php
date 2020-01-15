<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $outputPizzas = [];

    public function solve()
    {
        // algo
        $remaining = $this->maxSlices;
        arsort($this->pizzas);
        foreach ($this->pizzas as $id => $slices) {
            if($slices <= $remaining) {
                $this->outputPizzas[] = $id;
                $remaining -= $slices;
            }
        }

        // output (don't touch below)
        $score = 0;
        foreach ($this->outputPizzas as $id) {
            $score += $this->pizzas[$id];
        }
        asort($this->outputPizzas);
        //$this->output(count($this->outputPizzas) . "\n" . implode(" ", array_values($this->outputPizzas))); //WARNING: in the order they are listed in the input
        echo "Input: " . $this->inputName . " ***** Score: " . $score . " / " . $this->maxSlices . " (" . round($score / $this->maxSlices * 100, 2) . "%) ***** Delta = " . ($this->maxSlices - $score) . "\n\n";
    }
}

new Solution('a_example');
new Solution('b_small');
new Solution('c_medium');
new Solution('d_quite_big');
new Solution('e_also_big');
