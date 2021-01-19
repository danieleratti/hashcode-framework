<?php

use Utils\Collection;
use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    /** @var int $id */
    public $id;
    /** @var int $award */
    public $award;

    /** @var Collection $inLibraries */
    public $inLibraries;

    public function __construct($id, $award)
    {
        $this->id = (int)$id;
        $this->award = (int)$award;
        $this->inLibraries = collect();
    }
}

class Library
{
    /** @var int $id */
    public $id;
    /** @var int $signUpDuration */
    public $signUpDuration;
    /** @var int $shipsPerDay */
    public $shipsPerDay;

    /** @var Collection $books */
    public $books;

    public function __construct($id, $fileRow1, $fileRow2)
    {
        global $books;
        $this->id = (int)$id;
        $this->books = [];
        list($booksCount, $this->signUpDuration, $this->shipsPerDay) = explode(' ', $fileRow1);
        $this->signUpDuration = (int)$this->signUpDuration;
        $this->shipsPerDay = (int)$this->shipsPerDay;
        foreach (explode(' ', $fileRow2) as $bookId) {
            /** @var Book $book */
            $bookId = (int)$bookId;
            $book = $books[$bookId];
            $this->books[$bookId] = $book;
            $book->inLibraries->put($id, $this);
        }
        $this->books = collect($this->books)->keyBy('id');
    }
}

/**
 * Runtime
 */

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($countBooks, $countLibraries, $countDays) = explode(' ', $content[0]);
$countBooks = (int)$countBooks;
$countLibraries = (int)$countLibraries;
$countDays = (int)$countDays;

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

/*
$books->map(function($v) {
    $v->inLibrariesCount = (int)$v->inLibraries->count();
    return $v;
});
*/

// Faster than inLibrariesCount!
//Stopwatch::tik('sortBy');
//$books->sortBy(function ($v, $key) { return $v->inLibraries->count(); });
//Stopwatch::tok('sortBy');
//Stopwatch::print();

$books = $books->filter(function ($b) {
    return $b->inLibraries->count() > 0;
});

