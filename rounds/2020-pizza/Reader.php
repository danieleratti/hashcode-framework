<?php

use HashCode;

require __DIR__ . '/../../bootstrap.php';

class Reader extends HashCode
{
    public $maxSlices; /* maximum number of slices to reach but not surpass */
    public $pizzas; /* [pizzaId => numSlices] */

    public function readInput()
    {
        list($maxSlices, $pizzas) = explode("\n", $this->inputContent);

        list($maxSlices) = explode(" ", $maxSlices);
        $this->maxSlices = (int)($maxSlices);

        $pizzas = explode(" ", $pizzas);
        foreach($pizzas as $id => $slices) {
            $this->pizzas[] = (int)$slices;
        }
    }
}
