<?php

require __DIR__ . '/Reader.php';

class Solution extends Reader
{
    public function solve()
    {
        //Calculate solution
        $this->output('example output ' . $this->foo);
    }
}

new Solution('testinput');
