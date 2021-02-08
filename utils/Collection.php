<?php

namespace Utils;

use Tightenco\Collect\Support\Arr;

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

    /**
     * Get and remove the item with the maximum value of the given key.
     *
     * @param string $key
     * @return mixed
     */
    public function pullMax($key) {
        $maxIndex = null;
        $maxValue = null;
        foreach ($this->items as $index => $item) {
            if($maxValue === null || $item->{$key} > $maxValue) {
                $maxIndex = $index;
                $maxValue = $item->{$key};
            }
        }

        return Arr::pull($this->items, $maxIndex);
    }

    /**
     * Get and remove the item with the minimum value of the given key.
     *
     * @param string $key
     * @return mixed
     */
    public function pullMin($key) {
        $minIndex = null;
        $minValue = null;
        foreach ($this->items as $index => $item) {
            if($minValue === null || $item->{$key} < $minValue) {
                $minIndex = $index;
                $minValue = $item->{$key};
            }
        }

        return Arr::pull($this->items, $minIndex);
    }
}
