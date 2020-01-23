<?php

namespace Utils;

class Collection extends \Tightenco\Collect\Support\Collection
{
    public function sortDesc()
    {
        return parent::sort(function ($a, $b) {
            if ($a == $b)
                return 0;
            return ($a > $b) ? -1 : 1;
        });
    }
}
