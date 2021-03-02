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

class Replier
{
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;

    public function __construct($company, $bonus)
    {
        $this->company = $company;
        $this->bonus = (int)$bonus;
    }
}

class Developer extends Replier
{
    /** @var string[] $skills */
    public $skills;

    public function __construct($company, $bonus, $skills)
    {
        parent::__construct($company, $bonus);
        $this->skills = $skills;
    }
}

class Manager extends Replier
{
    public function __construct($company, $bonus)
    {
        parent::__construct($company, $bonus);
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
    $DEVELOPERS[] = new Developer($company, $bonus, $skills);
}
array_splice($content, 0, $devsCount);

// Managers
$managersCount = (int)$content[0];
array_shift($content);
/** @var Manager[] $MANAGERS */
$MANAGERS = [];
for ($i = 0; $i < $managersCount; $i++) {
    [$company, $bonus] = explode(' ', $content[$i], 4);
    $MANAGERS[] = new Manager($company, $bonus);
}
array_splice($content, 0, $managersCount);
