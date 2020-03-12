<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

ini_set('xdebug.max_nesting_level', 10000);

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

function getScore()
{
    global $tiles;
    $score = 0;
    foreach ($tiles->where('isOccupied', true) as $tile) {
        /** @var Tile $tile */
        /** @var Tile $tile2 */
        foreach ($tile->nears as $tile2) {
            if ($tile2->isOccupied) {
                $score += getPairScore($tile->people, $tile2->people);
            }
        }
    }
    return $score / 2;
}

function getPairScore(People $p1, People $p2)
{
    $score = 0;
    if ($p1 instanceof Developer && $p2 instanceof Developer)
        $score += count(peopleCommonSkills($p1, $p2)) * count(peopleUniqueSkills($p1, $p2));
    if ($p1->company == $p2->company)
        $score += $p1->bonus * $p2->bonus;
    return $score;
}

function commonSkills($skills1, $skills2)
{
    return array_intersect($skills1, $skills2);
}

function uniqueSkills($skills1, $skills2)
{
    $all = [];
    foreach ($skills1 as $s)
        $all[$s] = true;
    foreach ($skills2 as $s)
        $all[$s] = true;
    return array_diff(array_keys($all), array_intersect($skills1, $skills2));
}

function peopleCommonSkills(People $p1, People $p2)
{
    return ($p1 instanceof Developer && $p2 instanceof Developer) ? commonSkills($p1->skills, $p2->skills) : 0;
}

function peopleUniqueSkills(People $p1, People $p2)
{
    return ($p1 instanceof Developer && $p2 instanceof Developer) ? uniqueSkills($p1->skills, $p2->skills) : 0;
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
        if (!$tile->isDesk) die("FATAL: Stai tentando di occupare $r,$c che non è Desk");
        $tile->occupy($this);
    }
}

class Developer extends People
{
    /** @var string[] $skills */
    public $skills;
    /** @var int $skillsCount */
    public $skillsCount;

    public function __construct($id, $company, $bonus, $skills)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
        $this->skills = $skills;
        $this->skillsCount = count($skills);
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
    /** @var int $id */
    public $id;
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
    /** @var int $nearsCount */
    public $nearsCount = 0;
    /** @var int $nearsUsedCount */
    public $nearsUsedCount = 0;
    /** @var int $nearsFreeCount */
    public $nearsFreeCount = 0;
    /** @var float $nearsUsedPerc */
    public $nearsUsedPerc = 0;
    /** @var People $people */
    public $people = null;

    public function __construct(string $cellLetter, int $r, int $c)
    {
        $this->r = (int)$r;
        $this->c = (int)$c;
        $this->isDesk = $cellLetter != '#';
        $this->isDevDesk = $cellLetter == '_';
        $this->isManagerDesk = $cellLetter == 'M';
        $this->id = $r . '-' . $c;
    }

    public function occupy(People $p)
    {
        global $skill2developers, $company2developers, $company2managers;
        $this->isOccupied = true;
        $this->people = $p;
        $p->placed = true;
        $p->r = $this->r;
        $p->c = $this->c;
        if ($p instanceof Developer) {
            foreach ($p->skills as $skill) {
                $skill2developers[$skill] = array_filter($skill2developers[$skill], function ($v) use ($p) {
                    if ($v->id == $p->id) return false;
                    return true;
                });
            }
            $company2developers[$p->company] = array_filter($company2developers[$p->company], function ($v) use ($p) {
                if ($v->id == $p->id) return false;
                return true;
            });
        } else {
            $company2managers[$p->company] = array_filter($company2managers[$p->company], function ($v) use ($p) {
                if ($v->id == $p->id) return false;
                return true;
            });
        }
        foreach($this->nears as $near) {
            $near->nearsFreeCount--;
            $near->nearsUsedCount++;
            $near->nearsUsedPerc = $near->nearsUsedCount / $near->nearsCount;
        }
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

// Variables
[$WIDTH, $HEIGHT] = explode(' ', $content[0]);
$numDevelopers = null;
$numManagers = null;

$developers = collect();
$managers = collect();
$tiles = collect();
$rcTiles = [];

$skill2developers = [];
$company2developers = [];
$company2managers = [];

$MAP = []; // # Unavailable, _ Developer, M ProjectManager [NON USARE]

// Reader
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
                $developer = new Developer(count($developers), $company, $bonus, $skills);
                $developers->add($developer);
                $company2developers[$company][] = $developer;
                foreach ($skills as $skill)
                    $skill2developers[$skill][] = $developer;
            } elseif (!$numManagers) {
                $numManagers = (int)$row;
            } else {
                $row = explode(" ", $row);
                $company = $row[0];
                $bonus = (int)$row[1];
                $manager = new Manager(count($managers), $company, $bonus);
                $managers->add($manager);
                $company2managers[$company][] = $manager;
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
    $tile->nearsCount = count($tile->nears);
    $tile->nearsFreeCount = count($tile->nears);
}

$managers = $managers->keyBy('id');
$developers = $developers->keyBy('id');
