<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    /** @var int $id */
    public $id;
    /** @var int $award */
    public $award;
    /** @var int $rAward */
    public $rAward;
    /** @var bool $scanned */
    public $scanned;

    /** @var Library[] $inLibraries */
    public $inLibraries = [];

    /** @var Library[] $originalInLibraries */
    public $originalInLibraries = [];

    public function __construct($id, $award)
    {
        $this->id = (int)$id;
        $this->award = (int)$award;
    }

    public function scan(Library $byLibrary)
    {
        $this->scanned = true;
        foreach ($this->inLibraries as $library) {
            unset($library->books[$this->id]);
            $library->currentTotalAward -= $this->award;
            $library->rCurrentTotalAward -= $this->rAward;
        }
        $byLibrary->scannedBooks[$this->id] = $this;
        //echo "Scan book {$this->id} by library {$byLibrary->id}\n";
    }

    public function scanFirst(Library $byLibrary)
    {
        $this->scanned = true;
        foreach ($this->inLibraries as $library) {
            unset($library->books[$this->id]);
            $library->currentTotalAward -= $this->award;
            $library->rCurrentTotalAward -= $this->rAward;
        }
        $byLibrary->scannedBooks[$this->id] = $this;

        uasort($byLibrary->scannedBooks, function (Book $b1, Book $b2) {
            return $b1->award < $b2->award;
        });

    }

    public function unscan(Library $byLibrary)
    {
        $this->scanned = false;
        foreach ($this->originalInLibraries as $library) {
            $library->books[$this->id] = $this;
            $library->currentTotalAward += $this->award;
            $library->rCurrentTotalAward += $this->rAward;
        }
        unset($byLibrary->scannedBooks[$this->id]);
    }

    public function unscanFirst(Library $byLibrary)
    {
        $this->scanned = false;
        foreach ($this->originalInLibraries as $library) {
            $library->books[$this->id] = $this;
            $library->currentTotalAward += $this->award;
            $library->rCurrentTotalAward += $this->rAward;
            uasort($library->books, function (Book $b1, Book $b2) {
                return $b1->award < $b2->award;
            });
        }
        unset($byLibrary->scannedBooks[$this->id]);
    }
}

class Library
{
    public $id;
    /** @var int $signUpDuration */
    public $signUpDuration;
    /** @var int $shipsPerDay */
    public $shipsPerDay;
    /** @var Book[] $books */
    public $books;
    /** @var Book[] $originalBooks */
    public $originalBooks;
    // Computed
    public $isSignupped = false;
    public $signupFinishAt = -1;
    public $currentTotalAward = 0;
    public $rCurrentTotalAward = 0;
    public $dCurrentTotalAward = 0;
    public $dLastChunkAward = 0;
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
        $this->originalBooks = $this->books;
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

    public function recalculateDCurrentTotalAward($remainingTime)
    {
        $this->dCurrentTotalAward = 0;
        $dLastChunkAward = 0;
        $maxBooksCount = ($remainingTime - $this->signUpDuration) * $this->shipsPerDay;
        foreach ($this->books as $book) {
            if ($maxBooksCount < 0) break;
            $this->dCurrentTotalAward += $book->award;
            if($maxBooksCount <= $this->shipsPerDay) {
                $dLastChunkAward += $book->award;
            }
            $maxBooksCount--;
        }
        
        if(ceil(count($this->books)/$this->shipsPerDay)+$this->signUpDuration >= $remainingTime) {
            $this->dLastChunkAward = $dLastChunkAward;
        } else {
            $this->dLastChunkAward = 0;
        }
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
