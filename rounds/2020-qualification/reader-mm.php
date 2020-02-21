<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    public $id;
    public $award;
    public $rAward;

    /** @var Library[] $inLibraries */
    public $inLibraries = [];

    public function __construct($id, $award)
    {
        $this->id = (int)$id;
        $this->award = (int)$award;
    }

    public function scan(Library $byLibrary)
    {
        foreach ($this->inLibraries as $library) {
            unset($library->books[$this->id]);
            $library->currentTotalAward -= $this->award;
            $library->rCurrentTotalAward -= $this->rAward;
        }
        $byLibrary->scannedBooks[$this->id] = $this;
        //echo "Scan book {$this->id} by library {$byLibrary->id}\n";
    }
}

class Library
{
    public $id;
    public $signUpDuration;
    public $shipsPerDay;
    /** @var Book[] $books */
    public $books;
    // Computed
    public $isSignupped = false;
    public $signupFinishAt = -1;
    public $currentTotalAward = 0;
    public $rCurrentTotalAward = 0;
    /** @var Book[] $scannedBooks */
    public $scannedBooks = [];

    public function __construct($id, $fileRow1, $fileRow2)
    {
        /** @var Book[] $books */
        global $books;
        $this->id = (int)$id;
        $this->books = [];
        [$booksCount, $this->signUpDuration, $this->shipsPerDay] = explode(' ', $fileRow1);
        foreach (explode(' ', $fileRow2) as $bookId) {
            /** @var Book $book */
            $book = $books[(int)$bookId];
            $this->books[(int)$bookId] = $books[(int)$bookId];
            $book->inLibraries[$this->id] = $this;
            $this->currentTotalAward += $this->books[(int)$bookId]->award;
        }
        uasort($this->books, function (Book $b1, Book $b2) {
            return $b1->award < $b2->award;
        });
    }

    public function startSignup($now)
    {
        $this->signupFinishAt = $now + $this->signUpDuration;
        //echo "Start signup for {$this->id}\n";
    }

    public function finishSignup()
    {
        $this->isSignupped = true;
        //echo "Finish signup for {$this->id}\n";
    }
}

/**
 * Runtime
 */

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$countBooks, $countLibraries, $countDays] = explode(' ', $content[0]);

$books = [];
foreach (explode(' ', $content[1]) as $id => $bookAward) {
    $books[$id] = new Book($id, $bookAward);
}
//$books = collect($books);

$libraries = [];
$librariesRows = array_slice($content, 2, count($content));

$id = 0;
for ($line = 0; $line < count($librariesRows); $line += 2) {
    $libraries[$id] = new Library($id, $librariesRows[$line], $librariesRows[$line + 1]);
    $id++;
}
//$libraries = collect($libraries);
