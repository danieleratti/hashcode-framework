<?php

use Utils\Collection;
use Utils\FileManager;

require_once '../../bootstrap.php';

/**
 * Runtime
 */

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($countBooks, $countLibraries, $countDays) = explode(' ', $content[0]);

$books = [];
foreach (explode(' ', $content[1]) as $id => $bookAward) {
    $books[$id] = new Book($id, $bookAward);
}
$books = collect($books)->keyBy('id');

$libraries = [];
$librariesRows = array_slice($content, 2, count($content));

$id = 0;
for ($line = 0; $line < count($librariesRows); $line += 2) {
    $libraries[$id] = new Library($id, $librariesRows[$line], $librariesRows[$line + 1]);
    $id++;
}
$libraries = collect($libraries)->keyBy('id');
