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

    public function occupy($r, $c)
    {
        global $rcTiles;
        /** @var Tile $tile */
        $tile = $rcTiles[$r][$c];
        if(!$tile->isDesk) die("FATAL: Stai tentando di occupare $r,$c che non è Desk");
        $tile->occupy($this);
    }
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
    /** @var bool $isDesk */
    public $isDesk;
    /** @var bool $isDevDesk */
    public $isDevDesk;
    /** @var bool $isManagerDesk */
    public $isManagerDesk;
    /** @var bool $isOccupied */
    public $isOccupied = false;
    /** @var array $nears */
    public $nears = [];
    /** @var People $people */
    public $people = null;

    public function __construct(string $cellLetter, int $r, int $c)
    {
        $this->r = (int)$r;
        $this->c = (int)$c;
        $this->isDesk = $cellLetter != '#';
        $this->isDevDesk = $cellLetter == '_';
        $this->isManagerDesk = $cellLetter == 'M';
    }

    public function occupy(People $p)
    {
        $this->isOccupied = true;
        $this->people = $p;
        $p->placed = true;
        $p->r = $this->r;
        $p->c = $this->c;
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
$rcTiles = [];

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
    foreach ($rows as $c => $val) {
        $tile = new Tile($val, $r, $c);
        $rcTiles[$r][$c] = $tile;
        $tiles->add($tile);
    }
}

foreach ($tiles as $tile) {
    /** @var Tile $tile */
    /** @var Tile $_tile */
    foreach ([[0, -1], [0, 1], [-1, 0], [1, 0]] as $delta) {
        $_tile = $rcTiles[$tile->r + $delta[0]][$tile->c + $delta[1]];
        if ($_tile && $_tile->isDesk)
            $tile->nears[] = $_tile;
    }
}

$managers = $managers->keyBy('id');
$developers = $developers->keyBy('id');
