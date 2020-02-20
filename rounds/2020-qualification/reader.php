<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

/**
 * Reader Classes
 */
class Foo
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

/**
 * Reader Functions
 */

function Bar($a)
{
    return $a;
}

/**
 * Runtime
 */

// Reading the inputs
$fileManager = new FileManager($fileName);
$fileContent = $fileManager->get();

$rows = explode("\n", $fileContent);
list($N) = explode(' ', $rows[0]);
array_shift($rows); //remove 1st element

/*
$rides = collect();
foreach ($rows as $i => $row) {
    $row = explode(' ', $row);
    $rides->add(new Ride($i, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]));
}
$rides = $rides->keyBy('id');
*/
