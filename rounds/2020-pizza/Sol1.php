<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $outputPizzas = [];

    public function solve()
    {
        // algo
        $this->outputPizzas[] = 1;
        $this->outputPizzas[] = 2;
        $this->outputPizzas[] = 0;

        // output (don't touch below)
        $score = 0;
        foreach ($this->outputPizzas as $id) {
            $score += $this->pizzas[$id];
        }
        asort($this->outputPizzas);
        //$this->output(count($this->outputPizzas) . "\n" . implode(" ", array_values($this->outputPizzas))); //WARNING: in the order they are listed in the input
        echo "Input: " . $this->inputName . " ***** Score: " . $score . " / " . $this->maxSlices . " (" . round($score/$this->maxSlices*100, 2) ."%)";
    }
}

new Solution('a_example');
//new Solution('b_small');
//new Solution('c_medium');
//new Solution('d_quite_big');
//new Solution('e_also_big');
