<?php

namespace Utils;

use Illuminate\Support\Arr;

class Collection extends \Illuminate\Support\Collection
{
    public function sortDesc($options = SORT_REGULAR): Collection
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
     * @param string|int $key
     * @return mixed
     */
    public function pullMax(string|int $key): mixed
    {
        $maxIndex = null;
        $maxValue = null;
        foreach ($this->items as $index => $item) {
            if ($maxValue === null || $item->{$key} > $maxValue) {
                $maxIndex = $index;
                $maxValue = $item->{$key};
            }
        }

        return Arr::pull($this->items, $maxIndex);
    }

    /**
     * Get and remove the item with the minimum value of the given key.
     *
     * @param string|int $key
     * @return mixed
     */
    public function pullMin(string|int $key): mixed
    {
        $minIndex = null;
        $minValue = null;
        foreach ($this->items as $index => $item) {
            if ($minValue === null || $item->{$key} < $minValue) {
                $minIndex = $index;
                $minValue = $item->{$key};
            }
        }

        return Arr::pull($this->items, $minIndex);
    }
}
