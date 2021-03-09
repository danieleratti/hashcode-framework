<?php

use Utils\Collection;
use Utils\FileManager;

require_once '../../bootstrap.php';

// Classes
abstract class Employee
{
    /** @var int $id */
    public $id;
    /** @var Company $company */
    public $company;
    /** @var int $bonus */
    public $bonus;
    /** @var int $posH */
    public $posH;
    /** @var int $posW */
    public $posW;

    public function __construct($company, $bonus)
    {
        $this->bonus = (int)$bonus;
        $this->company = $company;
    }
}

class Developer extends Employee
{
    private static $lastId = 0;
    /** @var Skill[] $skills */
    public $skills;

    public function __construct(Company $company, $bonus, $skills)
    {
        parent::__construct($company, $bonus);
        $this->id = self::$lastId++;
        foreach ($skills as $s) {
            $s->inDevelopers[] = $this;
        }
        $this->skills = $skills;
        $company->inDevelopers[] = $this;
    }
}

class ProjectManager extends Employee
{
    private static $lastId = 0;

    public function __construct(Company $company, $bonus)
    {
        parent::__construct($company, $bonus);
        $this->id = self::$lastId++;
        $company->inProjectManagers[] = $this;
    }
}

class Skill
{
    private static $lastId = 0;
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var Developer[] */
    public $inDevelopers;

    public function __construct($name)
    {
        $this->id = self::$lastId++;
        $this->name = $name;
        $this->inDevelopers = [];
    }

    public function __toString()
    {
        return $this->name;
    }
}

class Company
{
    private static $lastId = 0;
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var Collection */
    public $inDevelopers;
    /** @var Collection */
    public $inProjectManagers;

    public $mediumBonus;

    public $couples;

    public function __construct($name)
    {
        $this->id = self::$lastId++;
        $this->name = $name;
        $this->inDevelopers = [];
        $this->inProjectManagers = [];
    }
}

class Map
{
    /** @var Cell[][] $map */
    public $map;
    /** @var int $width */
    public $width;
    /** @var int $height */
    public $height;
    /** @var int $developersCell */
    public $developersCell;
    /** @var int $managerCells */
    public $managerCells;

    public function __construct($map, $height, $width, $devCells, $manCells)
    {
        $this->map = $map;
        $this->height = $height;
        $this->width = $width;
        $this->developersCell= $devCells;
        $this->managerCells= $manCells;
    }

    /**
     * @param Cell $currentCell
     * @param string $type
     * @return false|Cell
     */
    public function getFirstFreeNeighbour(Cell $currentCell, string $type) {
        $freePositions = $this->getFreeNeighbours($currentCell, $type);

        return $freePositions[0] ?? false;
    }

    public function getFreeNeighbours(Cell $cell, $type){
        $x=$cell->x;
        $y=$cell->y;
        $freePositions = [];
        if($x+1<=$this->width){
            if($this->map[$y][$x+1]->assignedTo===null && $this->map[$y][$x+1]->type===$type)
                $freePositions[] = $this->map[$y][$x+1];
                //$freePositions[]=['x'=>$x+1, 'y'=>$y];
        }
        if($x-1>=0){
            if($this->map[$y][$x-1]->assignedTo===null && $this->map[$y][$x-1]->type===$type)
                $freePositions[] = $this->map[$y][$x-1];
                //$freePositions[]=['x'=>$x-1, 'y'=>$y];
        }
        if($y-1>=0){
            if($this->map[$y-1][$x]->assignedTo===null && $this->map[$y-1][$x]->type===$type)
                $freePositions[] = $this->map[$y-1][$x];
                //$freePositions[]=['x'=>$x, 'y'=>$y-1];
        }
        if($y+1<=$this->height){
            if($this->map[$y+1][$x]->assignedTo===null && $this->map[$y+1][$x]->type===$type)
                $freePositions[] = $this->map[$y+1][$x];
                //$freePositions[]=['x'=>$x, 'y'=>$y+1];
        }
        return $freePositions;

    }
}

class Cell
{
    /** @var string */
    public $type;
    /** @var Employee */
    public $assignedTo;
    /** @var int $x */
    public $x;
    /** @var int $y */
    public $y;

    public function __construct($type, $x, $y )
    {
        $this->type = $type;
        $this->x=$x;
        $this->y=$y;
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($WIDTH, $HEIGHT) = explode(' ', $content[0]);

$devCells=0;
$managerCells=0;
for ($i = 0; $i < $HEIGHT; $i++) {
    $column = str_split($content[1 + $i]);
    foreach ($column as $j => $r) {
        $map[$i][$j] = new Cell($r, $j, $i);
        if($r==='_')
            $devCells++;
        if($r==='M')
            $managerCells++;
    }
}

$MAP = new Map($map, $HEIGHT, $WIDTH, $devCells, $managerCells);

list($NDEVELOPERS) = explode(' ', $content[1 + $HEIGHT]);

$SKILLS = [];
$COMPANIES = [];
$DEVELOPERS = [];
for ($i = 0; $i < $NDEVELOPERS; $i++) {
    $prop = explode(' ', $content[2 + $HEIGHT + $i]);
    $skills = array_slice($prop, 3);
    $devSkills = [];
    foreach ($skills as $s) {
        $tempSkill = $SKILLS[$s];
        if (!$tempSkill) {
            $tempSkill = new Skill($s);
            $SKILLS[$s] = $tempSkill;
        }
        $devSkills[] = $tempSkill;
    }
    $tempCompany = $COMPANIES[$prop[0]];
    if (!$tempCompany) {
        $tempCompany = new Company($prop[0]);
        $COMPANIES[$prop[0]] = $tempCompany;
    }
    $DEVELOPERS[] = new Developer($tempCompany, $prop[1], $devSkills);
}

list($NPROJECTMANAGERS) = explode(' ', $content[2 + $HEIGHT + $NDEVELOPERS]);

$PROJECTMANAGERS = [];
for ($i = 0; $i < $NPROJECTMANAGERS; $i++) {
    $prop = explode(' ', $content[3 + $HEIGHT + $i + $NDEVELOPERS]);
    $tempCompany = $COMPANIES[$prop[0]];
    if (!$tempCompany) {
        $tempCompany = new Company($prop[0]);
        $COMPANIES[$prop[0]] = $tempCompany;
    }
    $PROJECTMANAGERS[] = new ProjectManager($tempCompany, $prop[1]);
}

foreach ($COMPANIES as $c) {
    /** @var Company $c */
    $c->inDevelopers = collect($c->inDevelopers)->keyBy('id');
    $c->inProjectManagers = collect($c->inProjectManagers)->keyBy('id');
}

