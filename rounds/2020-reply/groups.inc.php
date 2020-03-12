<?php

class Group
{
    public static $_currentGroupId = 0;
    /** @var int $id */
    public $id;
    /** @var Tile[] $tiles */
    public $tiles = [];
    /** @var int $devCount */
    public $devCount = 0;
    /** @var int $managerCount */
    public $managerCount = 0;

    public function __construct()
    {
        $this->id = self::$_currentGroupId++;
    }

    public function addTile(Tile $t)
    {
        $this->tiles[] = $t;
        if ($t->isDevDesk) $this->devCount++;
        elseif ($t->isManagerDesk) $this->managerCount++;
    }
}

function createGroup(Tile $t, array &$groups, array &$tile2Group, &$tempGroupIdx)
{
    if (!$t->isDesk || isset($tile2Group[$t->r . ',' . $t->c])) return;
    $tile2Group[$t->r . ',' . $t->c] = $tempGroupIdx;
    $groups[$tempGroupIdx][] = $t;
    foreach ($t->nears as $n) {
        //echo $tempGroupIdx;
        createGroup($n, $groups, $tile2Group, $tempGroupIdx);
    }
}

$groups = [];
$tile2Group = [];
$tempGroupIdx = 0;
foreach ($tiles as $tile) {
    /** @var Tile $tile */
    createGroup($tile, $groups, $tile2Group, $tempGroupIdx);
    $tempGroupIdx++;
}
$groups = array_map(function ($tg) {
    $g = new Group();
    foreach ($tg as $item)
        $g->addTile($item);
    return $g;
}, $groups);
