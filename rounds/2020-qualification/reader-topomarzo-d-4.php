<?php

use Utils\Collection;
use Utils\FileManager;

require_once '../../bootstrap.php';

class Book
{
    public $id;
    public $takenTimes;

    /** @var array $inLibraries */
    /** @var array $inLibrariesRemaining */
    public $inLibraries;
    public $inLibrariesRemaining;
    /** @var int $inLibrariesCount */
    /** @var int $inLibrariesRemainingCount */
    public $inLibrariesCount;
    public $inLibrariesRemainingCount;
    public $shuffled;

    public function __construct($id, $award)
    {
        $id = (int)$id;
        $this->id = $id;
        $this->inLibraries = [];
        $this->inLibrariesRemaining = [];
        $this->takenTimes = 0;
        $this->shuffled = false;
    }
}

class Library
{
    public $id;
    public $taken = false;
    public $signUpDuration;
    public $shipsPerDay;

    /** @var Collection $books */
    /** @var Collection $booksChunked */
    /** @var Collection $booksChunkedScore */
    public $books;
    public $booksCount;
    public $booksRemaining;
    public $booksRemainingCount;
    public $uniqueBooks = [];
    public $uniqueBooksCount;

    public function __construct($id, $fileRow1, $fileRow2)
    {
        global $books;
        $id = (int)$id;
        $this->id = $id;
        $this->books = [];
        $this->uniqueBooksCount = 0;
        list($booksCount, $this->signUpDuration, $this->shipsPerDay) = explode(' ', $fileRow1);
        foreach (explode(' ', $fileRow2) as $bookId) {
            /** @var Book $book */
            $book = $books[$bookId];
            $this->books[] = $bookId;
            $this->booksRemaining[] = $bookId;
            $book->inLibraries[] = $id;
            $book->inLibrariesCount = count($book->inLibraries);
            $book->inLibrariesRemaining[] = $id;
            $book->inLibrariesRemainingCount = count($book->inLibraries);
        }
        $this->booksCount = (int)$booksCount;
        $this->booksRemainingCount = (int)$booksCount;
    }

    public function take($unique=false)
    {
        /** @var Collection $books */
        /** @var Collection $libraries */
        global $books, $libraries, $score, $currentDay;

        $this->taken = true;
        if(!$unique)
            $score += $this->booksRemainingCount * 65;
        else
            $score += $this->uniqueBooksCount * 65;
        $currentDay += 2;

        foreach ($this->books as $bookId) {
            $book = $books[$bookId];
            $book->inLibrariesRemaining = array_diff($book->inLibrariesRemaining, [$this->id]);
            $book->inLibrariesRemainingCount--;
            $book->takenTimes++;
        }
        foreach ($this->booksRemaining as $bookId) {
            $book = $books[$bookId];
            $this->booksRemaining = array_diff($this->booksRemaining, [$book->id]);
            $this->booksRemainingCount--;
            foreach ($book->inLibrariesRemaining as $libraryId) {
                $library = $libraries[$libraryId];
                $library->booksRemaining = array_diff($library->booksRemaining, [$book->id]);
                $library->booksRemainingCount--;
            }
        }
    }

    public function untake()
    {
        /** @var Collection $books */
        /** @var Collection $libraries */
        global $books, $libraries, $score, $currentDay;

        $this->taken = false;
        $score -= $this->uniqueBooksCount * 65;
        $currentDay -= 2;

        foreach ($this->books as $bookId) {
            $book = $books[$bookId];
            $book->inLibrariesRemaining[] = $this->id;
            $book->inLibrariesRemainingCount++;
            $book->takenTimes--;
        }

        foreach ($this->uniqueBooks as $bookId) {
            $book = $books[$bookId];
            foreach ($book->inLibraries as $libraryId) {
                $library = $libraries[$libraryId];
                $library->booksRemaining[] = $book->id;
                $library->booksRemainingCount++;
            }
        }
    }

    public function calcUnique()
    {
        global $books, $libraries;
        $this->uniqueBooks = [];
        $this->uniqueBooksCount = 0;
        foreach ($this->books as $bookId) {
            /** @var Book $book */
            $book = $books[$bookId];
            if ($this->taken) {
                if ($book->takenTimes == 1) {
                    $this->uniqueBooks[] = $bookId;
                    $this->uniqueBooksCount++;
                }
            } else {
                if ($book->takenTimes == 0) {
                    $this->uniqueBooks[] = $bookId;
                    $this->uniqueBooksCount++;
                }
            }
        }
    }

    public function switchWithLib($libAltId)
    {
        /** @var Library[] $libraries */
        /** @var Library $library */
        global $libraries, $books;
        $this->untake();
        $libraries[$libAltId]->take(true);

        $this->shuffled = true;
        $libraries->shuffled = true;

        $this->calcUnique(); //implicit
        $libraries[$libAltId]->calcUnique();
        foreach ($this->books as $bookId) {
            $book = $books[$bookId];
            foreach ($book->inLibraries as $libraryId) {
                $library = $libraries[$libraryId];
                $library->calcUnique();
            }
        }
        foreach ($libraries[$libAltId]->books as $bookId) {
            $book = $books[$bookId];
            foreach ($book->inLibraries as $libraryId) {
                $library = $libraries[$libraryId];
                $library->calcUnique();
            }
        }
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
$books = collect($books)->keyBy('id');

$libraries = [];
$librariesRows = array_slice($content, 2, count($content));

$id = 0;
for ($line = 0; $line < count($librariesRows); $line += 2) {
    $libraries[$id] = new Library($id, $librariesRows[$line], $librariesRows[$line + 1]);
    $id++;
}
$libraries = collect($libraries)->keyBy('id');
