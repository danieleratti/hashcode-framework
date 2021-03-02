<?php

use Utils\Log;
use Utils\FileManager;
use Utils\Stopwatch;

require_once '../../bootstrap.php';

global $fileName;

// Classes

class Employee
{
    /** @var string $type */
    public $type;
    /** @var int $id */
    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;
    /** @var int $numSkills */
    public $numSkills;
    /** @var string[] $skills */
    public $skills;
    /** @var int[] $coordinates */
    public $coordinates = [];

    public function __construct($type, $id, $company, $bonus, $skills)
    {
        $this->type = $type;
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

list($width, $height) = explode(' ', $content[0]);

$office = [];
for ($i = 0; $i < $height; $i++) {
    $office[] = str_split($content[1 + $i]);
}

$numDevs = (int) $content[1 + $height];

$startingFrom = 2 + $height;
$employees = [];
$counter = 0;
$companies = [];
for ($i = 0; $i < $numDevs; $i++) {
    $devProps = explode(' ', $content[$startingFrom + $i]);
    $skills = array_splice($devProps, 3, count($devProps) - 1);
    $companies[$devProps[0]] += 1;
    $employees[] = new Employee('D', $i, $devProps[0], $devProps[1], $skills);
    // $developers[] = new Employee('D', $i, $devProps[0], $devProps[1], $skills);
    $counter ++;
}

$numProjManager = (int) $content[2 + $height + $numDevs];

$managers = [];
$startingFrom = 3 + $height + $numDevs;
for ($i = 0; $i < $numProjManager; $i++) {
    $managerProps = explode(' ', $content[$startingFrom + $i]);
    $employees[] = new Employee('M', $counter, $managerProps[0], $managerProps[1], []);
    // $managers[$counter] = new Employee('M', $counter, $managerProps[0], $managerProps[1], []);
    $companies[$managerProps[0]] += 1;
    $counter++;
}

Log::out("Finish input reading", 0);
