<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $outputPizzas = [];

    public function solve($inputName)
    {
        usort($this->pizzas, function ($a, $b) {
            return $a->slices < $b->slices;
        });

        $score = 0;

        /** @var Pizza $pizza */
        foreach ($this->pizzas as $pizza) {
            if ($score + $pizza->slices >= $this->maxSlices)
                continue;

            $solution[] = $pizza->i;
            $score += $pizza->slices;
        }

        $this->output(count($this->outputPizzas) . "\n" . implode(" ", $this->outputPizzas));
    }
}

$fileNames = [
    'a' => 'a_example',
    'b' => 'b_small',
    'c' => 'c_medium',
    'd' => 'd_quite_big',
    'e' => 'e_also_big',
];

foreach ($fileNames as $name) {
    new Solution($name);
}

// new Solution($fileNames['b']);
