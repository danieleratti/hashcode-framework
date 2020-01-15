<?php

require __DIR__ . '/../../bootstrap.php';

class Reader extends HashCode
{
    public $foo;

    public function readInput()
    {
        $rows = explode("\n", $this->inputContent);
        foreach($rows as $k => $v)
            $this->foo .= $v;
    }
}
