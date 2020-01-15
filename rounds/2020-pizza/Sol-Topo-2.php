<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $outputPizzas = [];

    public function solve()
    {
        // algo
        arsort($this->pizzas);
        $maxScore = 0;
        for($skip=0;$skip<10000;$skip++) {
            $_maxScore = max($maxScore, $this->solveWithSkip($skip));
            if($_maxScore > $maxScore) {
                echo "new maxScore = $_maxScore\n";
            }
            $maxScore = $_maxScore;
        }
        echo "Input: " . $this->inputName . " ***** Score: " . $maxScore . " / " . $this->maxSlices . " (" . round($maxScore / $this->maxSlices * 100, 2) . "%) ***** Delta = " . ($this->maxSlices - $maxScore) . "\n\n";
    }

    public function solveWithSkip($skip=0)
    {
        $this->outputPizzas = [];
        $remaining = $this->maxSlices;
        foreach ($this->pizzas as $id => $slices) {
            if ($slices <= $remaining) {
                if($skip > 0)
                    $skip--;
                else {
                    $this->outputPizzas[] = $id;
                    $remaining -= $slices;
                }
            }
        }

        // output (don't touch below)
        $score = 0;
        foreach ($this->outputPizzas as $id) {
            $score += $this->pizzas[$id];
        }
        //asort($this->outputPizzas);
        //$this->output(count($this->outputPizzas) . "\n" . implode(" ", array_values($this->outputPizzas))); //WARNING: in the order they are listed in the input
        return $score;
    }
}

//new Solution('a_example');
//new Solution('b_small');
//new Solution('c_medium');
new Solution('d_quite_big');
//new Solution('e_also_big');
