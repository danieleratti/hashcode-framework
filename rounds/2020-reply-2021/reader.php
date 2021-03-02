<?php

use Utils\FileManager;

require_once __DIR__ . '/../../bootstrap.php';

// Classes

class Cell
{
    /** @var int $r */
    public $r;
    /** @var int $c */
    public $c;
    /** @var string $type (D, M or null) */
    public $type;
    /** @var Cell[] $ */
    public $nears = [];

    public function __construct($r, $c, $type)
    {
        $this->r = (int)$r;
        $this->c = (int)$c;
        $this->type = $type === '_' ? 'D' : ($type === '#' ? null : 'M');
    }
}

abstract class Replier
{
    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;

    public $type;

    public $bestDevelopers = [];
    public $bestManagers = [];

    public function __construct($id, $company, $bonus)
    {
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
    }

    /** nella chiave l'id */
    public function initBestList()
    {
        $possibilities = $this->getPossibleRepliers();
        foreach ($possibilities as $replier) {
            if ($replier->id == $this->id)
                continue;

            $score = getCoupleScore($replier, $this);
            if ($replier instanceof Developer)
                $this->bestDevelopers[$replier->id] = $replier;
            else
                $this->bestManagers[$replier->id] = $replier;
        }

        arsort($this->bestManagers);
        asort($this->bestDevelopers);
    }

    public function getPossibleRepliers()
    {
        global $company2Managers, $company2Developers;
        return array_merge(
            array_values($company2Managers[$this->company]),
            array_values($company2Developers[$this->company])
        );
    }
}

class Developer extends Replier
{
    /** @var string[] $skills */
    public $skills;

    public function __construct($id, $company, $bonus, $skills)
    {
        parent::__construct($id, $company, $bonus);
        $this->skills = $skills;
    }

    public function getPossibleRepliers()
    {
        global $company2Managers, $company2Developers, $skill2Developers;
        $ret = parent::getPossibleRepliers();
        foreach ($this->skills as $skill) {
            foreach ($skill2Developers[$skill] as $developer)
                $ret[] = $developer;
        }
        return $ret;
    }
}

class Manager extends Replier
{
    public function __construct($id, $company, $bonus)
    {
        parent::__construct($id, $company, $bonus);
    }
}

// Reading the inputs

$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

// Globals
[$columnsCount, $rowsCount] = explode(' ', $content[0]);
$columnsCount = (int)$columnsCount;
$rowsCount = (int)$rowsCount;
array_shift($content);

// Map
/** @var Cell[][] $MAP */
$MAP = [];
/** @var Cell[] $CELLS */
$CELLS = [];
for ($r = 0; $r < $rowsCount; $r++) {
    for ($c = 0; $c < $columnsCount; $c++) {
        if ($content[$r][$c] !== '#') {
            $cell = new Cell($r, $c, $content[$r][$c]);
            $MAP[$r][$c] = $cell;
            $CELLS[] = $cell;
        } else {
            $MAP[$r][$c] = null;
        }
    }
}
array_splice($content, 0, $rowsCount);
foreach ($CELLS as $cell) {
    if ($cell->c > 0 && $MAP[$cell->r][$cell->c - 1] !== null) {
        $cell->nears[] = $MAP[$cell->r][$cell->c - 1];
    }
    if ($cell->c < $columnsCount - 1 && $MAP[$cell->r][$cell->c + 1] !== null) {
        $cell->nears[] = $MAP[$cell->r][$cell->c + 1];
    }
    if ($cell->r > 0 && $MAP[$cell->r - 1][$cell->c] !== null) {
        $cell->nears[] = $MAP[$cell->r - 1][$cell->c];
    }
    if ($cell->r < $rowsCount - 1 && $MAP[$cell->r + 1][$cell->c] !== null) {
        $cell->nears[] = $MAP[$cell->r + 1][$cell->c];
    }
}

// Developers
$devsCount = (int)$content[0];
array_shift($content);
/** @var Developer[] $DEVELOPERS */
$DEVELOPERS = [];
for ($i = 0; $i < $devsCount; $i++) {
    [$company, $bonus, $skillsCount, $skills] = explode(' ', $content[$i], 4);
    $skills = explode(' ', $skills);
    $DEVELOPERS[] = new Developer($i, $company, $bonus, $skills);
}
array_splice($content, 0, $devsCount);

// Managers
$managersCount = (int)$content[0];
array_shift($content);
/** @var Manager[] $MANAGERS */
$MANAGERS = [];
for ($i = 0; $i < $managersCount; $i++) {
    [$company, $bonus] = explode(' ', $content[$i], 4);
    $MANAGERS[] = new Manager($i, $company, $bonus);
}
array_splice($content, 0, $managersCount);

$skill2Developers = [];
$company2Developers = [];
$company2Managers = [];

foreach ($DEVELOPERS as $developer) {
    foreach ($developer->skills as $skill) {
        $skill2Developers[$skill][$developer->id] = $developer;
    }
    $company2Developers[$developer->company][$developer->id] = $developer;
}

foreach ($MANAGERS as $manager) {
    $company2Managers[$manager->company][$manager->id] = $manager;
}
