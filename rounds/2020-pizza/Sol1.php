<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public $outputPizzas = [];

    public function solve()
    {

        $this->output(count($this->outputPizzas) . "\n" . implode(" ", $this->outputPizzas)); //WARNING: in the order they are listed in the input
    }
}

new Solution('a_example');
//new Solution('b_small');
//new Solution('c_medium');
//new Solution('d_quite_big');
//new Solution('e_also_big');
