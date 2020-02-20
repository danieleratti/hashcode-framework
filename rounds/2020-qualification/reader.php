<?php

use Utils\Collection;
use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    public $id;
    public $award;

    /** @var Collection $inLibraries */
    public $inLibraries;

    public function __construct($id, $award)
    {
        $this->id = $id;
        $this->award = $award;
        $this->inLibraries = collect();
    }
}

class Library
{
    public $id;
    public $signUpDuration;
    public $shipsPerDay;

    /** @var Collection $books */
    public $books;

    public function __construct($id, $fileRow1, $fileRow2)
    {
        global $books;
        $this->id = $id;
        $this->books = [];
        list($booksCount, $this->signUpDuration, $this->shipsPerDay) = explode(' ', $fileRow1);
        foreach (explode(' ', $fileRow2) as $bookId) {
            /** @var Book $book */
            $book = $books[$bookId];
            $this->books[$bookId] = $book;
            $book->inLibraries->add($book);
        }
        $this->books = collect($this->books);
    }
}

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
$books = collect($books);

$libraries = [];
$librariesRows = array_slice($content, 2, count($content));

$id = 0;
for ($line = 0; $line < count($librariesRows); $line += 2) {
    $libraries[$id] = new Library($id, $librariesRows[$line], $librariesRows[$line + 1]);
    $id++;
}
$libraries = collect($libraries);
