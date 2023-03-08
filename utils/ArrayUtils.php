<?php

namespace Utils;

class ArrayUtils
{
    /*
     * Reorder an array by 2 given keys (ORDER BY $_key $sort, $_key2 $sort2)
     */
    public static function array_double_keysort(&$data, $_key, $sort, $_key2, $sort2 = SORT_DESC): void
    {
        foreach ($data as $key => $row) {
            $sorter[$key] = $row[$_key];
            $sorter2[$key] = $row[$_key2];
        }
        array_multisort($sorter, $sort, $sorter2, $sort2, $data);
    }

    /*
     * Reorder an array by a given key (ORDER BY $_key $sort)
     */
    public static function array_keysort(&$data, $_key, $sort = SORT_DESC): void
    {
        $sorter = [];
        if ($sort == 'DESC')
            $sort = SORT_DESC;
        elseif ($sort == 'ASC')
            $sort = SORT_ASC;
        foreach ($data as $key => $row) {
            $sorter[$key] = $row[$_key];
        }
        array_multisort($sorter, $sort, $data);
    }

    /*
     * ArrayUtils::getAllCombinations(["a", "b"])
     * [{0: "a"},{"1":"b"},{"1":"b","0":"a"}]
     */

    public static function getAllCombinationsFlat($arr): array
    {
        $_comb = self::getAllCombinations($arr);
        $comb = [];
        foreach ($_comb as $_c) {
            $c = [];
            foreach ($_c as $v)
                $c[] = $v;
            $comb[] = $c;
        }
        return $comb;
    }

    /*
     * ArrayUtils::getAllCombinationsFlat(["a", "b"])
     * [[a], [a, b], [b]]
     */

    public static function getAllCombinations($arr): array
    {
        $firstId = key($arr);
        if (count($arr) === 1) {
            return [$firstId => $arr];
        }
        $first = $arr[$firstId];
        unset($arr[$firstId]);
        $combinations = self::getAllCombinations($arr);
        $newCombinations = $combinations;
        foreach ($newCombinations as &$item) {
            $item[$firstId] = $first;
        }
        return array_merge([[$firstId => $first]], $combinations, $newCombinations);
    }

    /*
    * ArrayUtils::getAllCombinationsFlatLimited(["a", "b"], 2)
    * [[a], [a, b], [b]]
    */

    public static function getSumForAllCombinationsValuesFlat($arr): array
    {
        $_comb = self::getSumForAllCombinationsValues($arr);
        $comb = [];
        foreach ($_comb as $sum => $_c) {
            $c = [];
            foreach ($_c as $v)
                $c[] = $v;
            $comb[$sum] = $c;
        }
        return $comb;
    }

    /*
     * ArrayUtils::getSumForAllCombinationsValues([1, 2])
     * {"1":[1],"3":{"1":2,"0":1},"2":{"1":2}}
     */

    public static function getSumForAllCombinationsValues($arr): array
    {
        $ret = [];
        foreach ($arr as $id => $val) {
            foreach ($ret as $sum => $null) {
                if (!isset($ret[$val + $sum])) {
                    $list = [$id => $val];
                    foreach ($null as $k2 => $v2)
                        $list[$k2] = $v2;
                    $ret[$val + $sum] = $list;
                }
            }
            $ret[$val] = [$id => $val];
        }
        return $ret;
    }

    /*
     * ArrayUtils::getSumForAllCombinationsValuesFlat([1, 2])
     * {1: [1], 3: [1, 2], 2: [2]}
     */

    public static function array_keysort_objects(&$data, $_key, $sort = SORT_DESC): void
    {
        if ($sort == SORT_DESC)
            uasort($data, fn($i1, $i2) => $i1->$_key < $i2->$_key);
        else
            uasort($data, fn($i1, $i2) => $i1->$_key > $i2->$_key);
    }

    public static function array_double_keysort_objects(&$data, $_key, $sort, $_key2, $sort2 = SORT_DESC): void
    {
        if ($sort == SORT_DESC)
            uasort($data, fn($i1, $i2) => $i1->$_key < $i2->$_key);
        else
            uasort($data, fn($i1, $i2) => $i1->$_key > $i2->$_key);

        uasort($data, function ($i1, $i2) use ($_key, $_key2, $sort, $sort2) {
            if ($i1->$_key == $i2->$_key) {
                if ($sort2 == SORT_ASC)
                    return $i1->$_key2 > $i2->$_key2;
                else
                    return $i1->$_key2 < $i2->$_key2;
            } elseif ($sort == SORT_ASC)
                return $i1->$_key > $i2->$_key;
            else
                return $i1->$_key < $i2->$_key;
        });
    }

    function getAllCombinationsFlatLimited($arr, $limit)
    {
        $alreadyExistentCombs = [];
        $comb = [];
        sort($arr);
        foreach ($arr as $v)
            $comb[1][] = [$v];
        for ($i = 2; $i <= $limit; $i++) {
            $comb[$i] = [];
            foreach ($comb[$i - 1] as $c) {
                foreach ($comb[1] as $v) {
                    if (!in_array($v[0], $c)) {
                        $newComb = array_merge($c, $v);
                        sort($newComb);
                        $newCombHash = md5(json_encode($newComb));
                        if (!$alreadyExistentCombs[$newCombHash]) {
                            $alreadyExistentCombs[$newCombHash] = true;
                            $comb[$i][] = $newComb;
                        }
                    }
                }
            }
        }
        return array_reduce($comb, function ($carry, $item) {
            return array_merge($carry, $item);
        }, []);
    }
}