<?php

function array_double_keysort(&$data, $_key, $sort=SORT_DESC, $_key2, $sort2=SORT_DESC)
{
    // Obtain a list of columns
    foreach ($data as $key => $row) {
        $sorter[$key]  = $row[$_key];
        $sorter2[$key]  = $row[$_key2];
    }

    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($sorter, $sort, $sorter2, $sort2, $data);
}

function array_keysort(&$data, $_key, $sort=SORT_DESC)
{
    // Obtain a list of columns
    foreach ($data as $key => $row) {
        $sorter[$key]  = $row[$_key];
    }

    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($sorter, $sort, $data);
}
