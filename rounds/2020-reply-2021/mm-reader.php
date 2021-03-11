<?php

use Utils\DirUtils;
use Utils\FileManager;
use Utils\Log;

require_once __DIR__ . '/../../bootstrap.php';

// Classes

class Cell
{
    public $id;
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
        $this->id = $r . " " . $c;
    }

    /**
     * @param Replier $replier
     */
    public function sit($replier)
    {
        global $freeDevelopers, $freeManagers, $freeDevelopersCells, $freeManagersCells;
        //$replier->
        $this->replier = $replier;
        $replier->cell = $this;
        $this->toBeChecked = false;
        // DEALLOCARE quello usato e ovunque ci sono riferimento (es. bestDevelopers, bestManagers)!
        if ($replier instanceof Developer) {
            foreach ($replier->originalBestDevelopers as $d) {
                /** @var Developer $d */
                unset($d->bestDevelopers[$replier->id]);
            }
            foreach ($replier->originalBestManagers as $m) {
                /** @var Manager $m */
                unset($m->bestDevelopers[$replier->id]);
            }
            unset($freeDevelopers[$replier->id]);
            unset($freeDevelopersCells[$this->id]);
        } else {
            foreach ($replier->originalBestDevelopers as $d) {
                /** @var Developer $d */
                unset($d->bestManagers[$replier->id]);
            }
            foreach ($replier->originalBestManagers as $m) {
                /** @var Manager $m */
                unset($m->bestManagers[$replier->id]);
            }
            unset($freeManagers[$replier->id]);
            unset($freeManagersCells[$this->id]);
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
    /** @var int $nextIntId */
    private static $nextIntId = 0;
    /** @var int $intId */
    public $intId;

    public $id;
    /** @var string $company */
    public $company;
    /** @var int $bonus */
    public $bonus;
    /** @var Cell $cell */
    public $cell;

    public $bestDevelopers = [];
    public $bestManagers = [];
    public $originalBestDevelopers = [];
    public $originalBestManagers = [];

    public function __construct($id, $company, $bonus)
    {
        $this->intId = static::$nextIntId++;
        $this->id = $id;
        $this->company = $company;
        $this->bonus = (int)$bonus;
    }

    /** nella chiave l'id */
    public function initBestList()
    {
        global $MANAGERS, $DEVELOPERS, $coupleScores;
        $bestDevelopers = [];
        $bestManagers = [];
        $possibilities = $this->getPossibleRepliers();
        foreach ($possibilities as $replier) {
            if ($replier->id == $this->id)
                continue;

            $score = $coupleScores[$replier->id][$this->id];
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
        $this->originalBestDevelopers = $this->bestDevelopers;
        $this->originalBestManagers = $this->bestManagers;
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

    /** @var bool[] $reverseSkills */
    public $reverseSkills;

    public function __construct($id, $company, $bonus, $skills)
    {
        parent::__construct("D_" . $id, $company, $bonus);
        $this->skills = $skills;
        $this->reverseSkills = array_flip($skills);
    }

    public function getPossibleRepliers()
    {
        global $skill2Developers;
        $ret = parent::getPossibleRepliers();

        /* versione 1) a,b,c(lento) */

        foreach ($this->skills as $skill) {
            foreach ($skill2Developers[$skill] as $developer)
                $ret[] = $developer;
        } // <-- RIMOSSA TEMPORANEAMENTE! RIMETTERE!!!!!!!


        /* versione 2) */
        /*$deltaSkillsToTake = 5;
        foreach ($this->skills as $skill) {
            foreach ($skill2Developers[$skill] as $developer)
                /* @var Developer $developer * /
                if (abs(count($developer->skills) - count($this->skills)) <= $deltaSkillsToTake)
                    $ret[] = $developer;
        }*/

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

function generateCoupleScores()
{
    global $coupleScores, $fileName, $DEVELOPERS, $MANAGERS;
    /** @var Replier[] $REPLIERS */
    $REPLIERS = array_merge($DEVELOPERS, $MANAGERS);
    usort($REPLIERS, function (Replier $r1, Replier $r2) {
        return $r1->intId > $r2->intId;
    });
    $coupleScores = [];

    $fileDir = DirUtils::getScriptDir() . '/cache/' . $fileName . '_compressed';
    if (file_exists($fileDir)) {
        $content = file_get_contents($fileDir);
        $pieces = unpack('i*', $content);
        $i = 1;
        while ($i < sizeof($pieces)) {
            $coupleScores[$REPLIERS[$pieces[$i]]->id][$REPLIERS[$pieces[$i + 1]]->id] = $pieces[$i + 2];
            $i += 3;
        }
        unset($content);
        //print_r($coupleScores);
    } else {
        $output = '';
        foreach ($REPLIERS as $r1) {
            Log::out('Scoring R ' . $r1->intId);
            foreach ($REPLIERS as $r2) {
                if ($r1 === $r2) break;
                if ($cs = getCoupleScore($r1, $r2)) {
                    $coupleScores[$r1->id][$r2->id] = $cs;
                    $coupleScores[$r2->id][$r1->id] = $cs;
                    //Log::out($r1->intId . ' ' . $r2->intId . ' ' . $cs);
                    $output .= pack('i', $r1->intId) . pack('i', $r2->intId) . pack('i', $cs);
                }
            }
        }

        $file = fopen($fileDir, 'w');
        fwrite($file, $output);
        fclose($file);
        unset($output);
        unset($file);
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
            $CELLS[$cell->id] = $cell;
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

generateCoupleScores();

Log::out("Populating " . count($DEVELOPERS) . " developers");
foreach ($DEVELOPERS as $idx => $developer) {
    Log::out("$fileName => $idx / " . count($DEVELOPERS));
    $developer->initBestList();
}

Log::out("Populating " . count($MANAGERS) . " managers");
foreach ($MANAGERS as $idx => $manager) {
    Log::out("$fileName => $idx / " . count($MANAGERS));
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
