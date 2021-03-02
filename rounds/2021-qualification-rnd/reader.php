<?php

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
        $company->inProjctManagers[] = $this;
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
}

class Company
{
    private static $lastId = 0;
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var Developer[] */
    public $inDevelopers;
    /** @var ProjectManager[] */
    public $inProjctManagers;

    public function __construct($name)
    {
        $this->id = self::$lastId++;
        $this->name = $name;
        $this->inDevelopers = [];
        $this->inProjctManagers = [];
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

    public function __construct($map, $height, $width)
    {
        $this->map = $map;
        $this->height = $height;
        $this->width = $width;
    }

    public function getFreeNeighbours(int $x, int $y, $type){
        $freePositions = [];
        if($x+1<=$this->width){
            if($this->map[$y][$x+1]->assignedTo===null && $this->map[$y][$x+1]->type===$type)
                $freePositions[]=['x'=>$x+1, 'y'=>$y];
        }
        if($x-1>=0){
            if($this->map[$y][$x-1]->assignedTo===null && $this->map[$y][$x-1]->type===$type)
                $freePositions[]=['x'=>$x-1, 'y'=>$y];
        }
        if($y-1>=0){
            if($this->map[$y-1][$x]->assignedTo===null && $this->map[$y-1][$x]->type===$type)
                $freePositions[]=['x'=>$x, 'y'=>$y-1];
        }
        if($y+1<=$this->height){
            if($this->map[$y+1][$x]->assignedTo===null && $this->map[$y+1][$x]->type===$type)
                $freePositions[]=['x'=>$x, 'y'=>$y+1];
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

    public function __construct($type)
    {
        $this->type = $type;
    }
}


// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($WIDTH, $HEIGHT) = explode(' ', $content[0]);

for ($i = 0; $i < $HEIGHT; $i++) {
    $row = str_split($content[1 + $i]);
    foreach ($row as $j => $r) {
        $map[$i][$j] = new Cell($r);
    }
}

$MAP = new Map($map, $HEIGHT, $WIDTH);

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

