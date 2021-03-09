<?php

use Utils\FileManager;
use Utils\Log;

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

    /** @var bool $toBeChecked */
    public $toBeChecked = true;
    /** @var Replier $replier */
    public $replier;

    public function __construct($r, $c, $type)
    {
        $this->r = (int)$r;
        $this->c = (int)$c;
        $this->type = $type === '_' ? 'D' : ($type === '#' ? null : 'M');
    }

    /**
     * @param Replier $replier
     */
    public function sit($replier)
    {
        global $freeDevelopers, $freeManagers;
        //$replier->
        $this->replier = $replier;
        $replier->cell = $this;
        $this->toBeChecked = false;
        // DEALLOCARE quello usato e ovunque ci sono riferimento (es. bestDevelopers, bestManagers)!
        if ($replier instanceof Developer) {
            foreach ($replier->bestDevelopers as $d) {
                /** @var Developer $d */
                unset($d->bestDevelopers[$replier->id]);
            }
            foreach ($replier->bestManagers as $m) {
                /** @var Manager $m */
                unset($m->bestDevelopers[$replier->id]);
            }
            unset($freeDevelopers[$replier->id]);
        } else {
            foreach ($replier->bestDevelopers as $d) {
                /** @var Developer $d */
                unset($d->bestManagers[$replier->id]);
            }
            foreach ($replier->bestManagers as $m) {
                /** @var Manager $m */
                unset($m->bestManagers[$replier->id]);
            }
            unset($freeManagers[$replier->id]);
        }
    }

    public function setEmpty()
    {
        if ($this->replier)
            die("hai fatto un errore poco dio");
        $this->toBeChecked = false;
    }
}

abstract class Replier
{
    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;
    /** @var Cell $cell */
    public $cell;

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
        global $MANAGERS, $DEVELOPERS;
        $bestDevelopers = [];
        $bestManagers = [];
        $possibilities = $this->getPossibleRepliers();
        foreach ($possibilities as $replier) {
            if ($replier->id == $this->id)
                continue;

            $score = getCoupleScore($replier, $this);
            if ($replier instanceof Developer)
                $bestDevelopers[$replier->id] = $score;
            else
                $bestManagers[$replier->id] = $score;
        }

        arsort($bestManagers);
        arsort($bestDevelopers);

        foreach ($bestManagers as $id => $score) {
            $this->bestManagers[$id] = $MANAGERS[$id];
        }
        foreach ($bestDevelopers as $id => $score) {
            $this->bestDevelopers[$id] = $DEVELOPERS[$id];
        }
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
        parent::__construct("D_" . $id, $company, $bonus);
        $this->skills = $skills;
    }

    public function getPossibleRepliers()
    {
        global $skill2Developers;
        $ret = parent::getPossibleRepliers();
        /*foreach ($this->skills as $skill) {
            foreach ($skill2Developers[$skill] as $developer)
                $ret[] = $developer;
        }*/ // <-- RIMOSSA TEMPORANEAMENTE! RIMETTERE!!!!!!!
        return $ret;
    }
}

class Manager extends Replier
{
    public function __construct($id, $company, $bonus)
    {
        parent::__construct("M_" . $id, $company, $bonus);
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
    $DEVELOPERS["D_" . $i] = new Developer($i, $company, $bonus, $skills);
}
array_splice($content, 0, $devsCount);

// Managers
$managersCount = (int)$content[0];
array_shift($content);
/** @var Manager[] $MANAGERS */
$MANAGERS = [];
for ($i = 0; $i < $managersCount; $i++) {
    [$company, $bonus] = explode(' ', $content[$i], 4);
    $MANAGERS["M_" . $i] = new Manager($i, $company, $bonus);
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

$freeDevelopers = $DEVELOPERS;
$freeManagers = $MANAGERS;
$freeDevelopersCells = collect($CELLS)->keyBy('id')->where('type', '=', 'D')->toArray();
$freeManagersCells = collect($CELLS)->keyBy('id')->where('type', '=', 'M')->toArray();

Log::out("Populating " . count($DEVELOPERS) . " developers");
foreach ($DEVELOPERS as $idx => $developer) {
    Log::out("$idx / " . count($DEVELOPERS));
    $developer->initBestList();
}

Log::out("Populating " . count($MANAGERS) . " managers");
foreach ($MANAGERS as $idx => $manager) {
    Log::out("$idx / " . count($MANAGERS));
    $manager->initBestList();
}

/*
     (/^\)
     (\ /)
     .-'-.
    /(_I_)\
    \\) (//
     /   \
     \ | /
      \|/
      /|\
      \|/
      /Y\
*/
