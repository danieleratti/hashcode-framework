<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

// Functions
function getOutput()
{
    global $developers, $managers;
    $ret = [];
    foreach ($developers->sortBy('id') as $dev) {
        if (!$dev->placed)
            $ret[] = "X";
        else
            $ret[] = $dev->c . " " . $dev->r;
    }
    foreach ($managers->sortBy('id') as $man) {
        if (!$man->placed)
            $ret[] = "X";
        else
            $ret[] = $man->c . " " . $man->r;
    }
    return implode("\n", $ret);
}

// Classes
class People
{
    /** @var int $id */
    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;
    /** @var bool $placed */
    public $placed = false;
    /** @var int $r */
    public $r = false; //y
    /** @var int $c */
    public $c = false; //x
}

class Developer extends People
{
    /** @var string[] $skills */
    public $skills;

    public function __construct($id, $company, $bonus, $skills)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
        $this->skills = $skills;
    }
}

class Manager extends People
{
    public function __construct($id, $company, $bonus)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
    }
}

class Tile
{
    /** @var int $r */
    public $r;
    /** @var int $c */
    public $c;
    /** @var bool $isAvailable */
    public $isAvailable;
    /** @var bool $isDevDesk */
    public $isDevDesk;
    /** @var bool $isManagerDesk */
    public $isManagerDesk;
    /** @var bool $isOccupied */
    public $isOccupied = false;

    /* TODO: vicini ($nears) */

    public function __construct(string $cellLetter, int $r, int $c)
    {
        $this->r = (int)$r;
        $this->c = (int)$c;
        $this->isAvailable = $cellLetter != '#';
        $this->isDevDesk = $cellLetter == '_';
        $this->isManagerDesk = $cellLetter == 'M';
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());


[$WIDTH, $HEIGHT] = explode(' ', $content[0]);
$numDevelopers = null;
$numManagers = null;

$developers = collect();
$managers = collect();
$tiles = collect();


$MAP = []; // # Unavailable, _ Developer, M ProjectManager

foreach ($content as $rowNumber => $row) {
    if ($rowNumber > 0) { /* skip first */
        if ($rowNumber <= $HEIGHT) { //floor
            $MAP[] = str_split($row, 1);
        } else {
            if (!$numDevelopers) {
                $numDevelopers = (int)$row;
            } elseif ($rowNumber - $HEIGHT - 1 <= $numDevelopers) {
                $row = explode(" ", $row);
                $company = $row[0];
                $bonus = (int)$row[1];
                $nSkills = (int)$row[2];
                $skills = array_slice($row, 3, $nSkills);
                $developers->add(new Developer(count($developers), $company, $bonus, $skills));
            } elseif (!$numManagers) {
                $numManagers = (int)$row;
            } else {
                $row = explode(" ", $row);
                $company = $row[0];
                $bonus = (int)$row[1];
                $managers->add(new Manager(count($managers), $company, $bonus));
            }
        }
    }
}

foreach ($MAP as $r => $rows) {
    foreach ($rows as $c => $val)
        $tiles->add(new Tile($val, $r, $c));
}

$managers = $managers->keyBy('id');
$developers = $developers->keyBy('id');
