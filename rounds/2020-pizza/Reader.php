<?php

require __DIR__ . '/../../bootstrap.php';

class Pizza
{
    public $slices;
    public $i;

    public function __construct($i, $slices)
    {
        $this->slices = $slices;
        $this->i = $i;
    }
}

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

        foreach ($pizzas as $i => $slices) {
            $this->pizzas[] = new Pizza($i, $slices);
        }
    }
}
