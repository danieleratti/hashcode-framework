<?php

use Utils\FileManager;

require_once '../../bootstrap.php';

// Classes
class People {
    /** @var string $company */
    /** @var int $bonus */
    public $company;
    public $bonus;

    public $placed = false;
    public $x = false;
    public $y = false;
}

class Developer extends People {
    public $skills;
    public function __construct($company, $bonus, $skills)
    {
        $this->company = $company;
        $this->bonus = (int)$bonus;
        $this->skills = $skills;
    }
}

class Manager extends People {
    public function __construct($company, $bonus)
    {
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
                echo "D= $row\n";
            } elseif (!$numManagers) {
                $numManagers = (int)$row;
            } else {
                echo "M= $row\n";
            }
        }
    }
}

