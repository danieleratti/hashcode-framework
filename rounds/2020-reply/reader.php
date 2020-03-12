<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

// Classes
class People {
    /** @var int $id */
    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;

    public $placed = false;
    public $r = false; //y
    public $c = false; //x
}

class Developer extends People {
    /** @var array $skills */
    public $skills;
    public function __construct($id, $company, $bonus, $skills)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
        $this->skills = $skills;
    }
}

class Manager extends People {
    public function __construct($id, $company, $bonus)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
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

$managers = $managers->keyBy('id');
$developers = $developers->keyBy('id');
