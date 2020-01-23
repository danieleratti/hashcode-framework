<?php

use \Src\Utils\ArrayUtils;

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $oldPizzas = [];
    public $outputPizzas = [];
    public $combinationsCodaSx;
    public $combinationsValuesCodaSx;
    public $maxCodaSx;
    public $currentBestDelta;
    public $bestPizzas;

    public function solve()
    {
        // config
        $nCodaSx = min(23, count($this->pizzas)-1);
        $this->currentBestDelta = 5;

        // algo
        $this->oldPizzas = $this->pizzas;
        $remaining = $this->maxSlices;

        $codaSx = array_slice($this->pizzas, 0, $nCodaSx, true);
        $this->pizzas = array_slice($this->pizzas, $nCodaSx, count($this->pizzas) - $nCodaSx, true);

        $this->combinationsCodaSx = ArrayUtils::getSumForAllCombinations($codaSx);
        $this->combinationsValuesCodaSx = array_keys($this->combinationsCodaSx);
        arsort($this->combinationsValuesCodaSx);
        $this->maxCodaSx = max(array_keys($this->combinationsCodaSx));

        arsort($this->pizzas);
        foreach ($this->pizzas as $id => $slices) {
            if ($slices <= $remaining) {
                $this->outputPizzas[] = $id;
                $remaining -= $slices;
            }
            if ($remaining - $this->maxSlices < $this->currentBestDelta) {
                $this->matchCombinations($remaining);
            }
        }

        // output (don't touch below)

        $score = 0;
        foreach ($this->bestPizzas as $id) {
            $score += $this->oldPizzas[$id];
        }

        asort($this->bestPizzas);

        $this->output(count($this->bestPizzas) . "\n" . implode(" ", array_values($this->bestPizzas))); //WARNING: in the order they are listed in the input
        echo "Input: " . $this->inputName . " ***** Score: " . $score . " / " . $this->maxSlices . " (" . round($score / $this->maxSlices * 100, 2) . "%) ***** Delta = " . ($this->maxSlices - $score) . "\n\n";

    }

    public function matchCombinations($remaining)
    {
        foreach($this->combinationsValuesCodaSx as $slicesCombinated) {
            if($slicesCombinated <= $remaining && ($remaining - $slicesCombinated) < $this->currentBestDelta) {
                $this->currentBestDelta = ($remaining - $slicesCombinated);
                $this->bestPizzas = [];
                foreach($this->combinationsCodaSx[ $slicesCombinated ] as $id => $v) {
                    $this->bestPizzas[] = $id;
                }
                foreach($this->outputPizzas as $v)
                    $this->bestPizzas[] = $v;
                return;
            }
        }
        return;
    }
}

new Solution('a_example');
new Solution('b_small');
new Solution('c_medium');
new Solution('d_quite_big');
new Solution('e_also_big');
