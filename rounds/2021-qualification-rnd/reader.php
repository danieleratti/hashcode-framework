<?php

use Utils\Log;
use Utils\FileManager;

require_once '../../bootstrap.php';

global $fileName = 'a_solar.tx';

// Classes

class Employee
{
    /** @var int $id */
    public $id;
    /** @var string $id */
    public $company;
    /** @var int $id */
    public $bonus;
    /** @var array $id */
    public $skills;
    /** @var int $posH */
    public $posH;
    /** @var int $posW */
    public $posW;

    public function __construct($type, $id, $company, $bonus, $skills)
    {
        $this->id = self::$lastId++;
        $this->id = $id;
        $this->company = $company;
        $this->bonus = $bonus;
        $this->numSkills = count($skills);
        $this->skills = $skills;
    }
}

// Reading the inputs
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

list($WIDTH, $HEIGHT) = explode(' ', $content[0]);

$officeFloor = [];
for ($i = 0; $i < $HEIGHT; $i++) {
    $officeFloor[] = str_split($content[1 + $i]);
}

list($NDEVELOPERS) = explode(' ', $content[1 + $HEIGHT]);

$EMPLOYEES = [];
for ($i = 0; $i < $NDEVELOPERS; $i++) {
    $properties = explode(' ', $content[2 + $HEIGHT + $i]);

}


