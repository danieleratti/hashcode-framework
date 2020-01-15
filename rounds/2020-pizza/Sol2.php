<?php

ini_set('memory_limit','4G');

require __DIR__ . '/Reader.php';

function printSolution($solution)
{
    return implode(" ", $solution);
}

class Solution extends Reader
{
    public $maxScoreFinded = 0;

    public $orderedPizzas;

    public function solve($inputName)
    {
        echo "---- $inputName -----\n";

        $this->orderedPizzas = $this->pizzas;
        usort($this->orderedPizzas, function ($a, $b) {
            return $a->slices < $b->slices;
        });

        $this->tryAll([], 0, 0);
    }

    public function tryAll($solution, $i, $score)
    {
        for (; $i < count($this->orderedPizzas); $i++) {
            $newScore = $score;
            $pizza = $this->orderedPizzas[$i];

            if ($newScore + $pizza->slices > $this->maxSlices)
                continue;

            $newScore += $pizza->slices;

            $newSolution = $solution;
            $newSolution[] = $pizza->i;

            if ($newScore > $this->maxScoreFinded)
                $this->myOutput($newSolution, $newScore);

            $this->tryAll($newSolution, $i + 1, $newScore);
        }
    }

    public function myOutput($solution, $score)
    {
        echo "best: $score\n";
        $this->maxScoreFinded = $score;
        $this->output(count($solution) . "\n" . printSolution($solution));

        if ($score == $this->maxSlices)
            die('MAXSCORE!!!');
    }
}

$fileNames = [
    'a' => 'a_example',
    'b' => 'b_small',
    'c' => 'c_medium',
    'd' => 'd_quite_big',
    'e' => 'e_also_big',
];

/*
foreach ($fileNames as $name)
    new Solution($name);
*/

new Solution($fileNames['e']);
