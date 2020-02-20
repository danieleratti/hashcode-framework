<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    public $id;
    public $award;

    public function __construct($id, $award)
    {
        $this->id = $id;
        $this->award = $award;
    }
}

class Library
{
    public $id;
    public $books;

    public function __construct($id, $fileRow)
    {
        global $books;
        $this->id = $id;
        $this->books = [];
        foreach (explode(' ', $fileRow) as $bookId) {
            $this->books[$bookId] = $books[$id];
        }
    }
}

/**
 * Runtime
 */

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
list($countBooks, $countLibraries, $countDays) = $content[0];

$books = [];
foreach (explode(' ', $content[1]) as $id => $bookAward) {
    $books[$id] = new Book($id, $bookAward);
}

$libraries = [];
$librariesRows = array_slice($content, 2, count($content));
foreach ($librariesRows as $id => $libraryRow) {
    $libraries[$id] = new Library($id, $libraryRow);
}
