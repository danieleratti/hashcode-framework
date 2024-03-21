<?php

global $fileName;

use Utils\FileManager;

require_once '../../bootstrap.php';

class GoldenPoint
{
    public int $x;
    public int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}

class SilverPoint
{
    public int $x;
    public int $y;
    public int $score;

    public function __construct(int $x, int $y, int $score)
    {
        $this->x = $x;
        $this->y = $y;
        $this->score = $score;
    }
}

class TileType
{
    public string $id;
    public int $cost;
    public int $count;

    public function __construct(string $id, int $cost, int $count)
    {
        $this->id = $id;
        $this->cost = $cost;
        $this->count = $count;
    }
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

[$W, $H, $Gn, $Sm, $Tl] = explode(' ', $content[0]);
$fileRow = 0;

/** @var int $W */
/** @var int $H */
/** @var int $Gn */
/** @var int $Sm */
/** @var int $Tl */
[$W, $H, $Gn, $Sm, $Tl] = explode(' ', $content[$fileRow++]);
$W = (int)trim($W);
$H = (int)trim($H);
$Gn = (int)trim($Gn);
$Sm = (int)trim($Sm);
$Tl = (int)trim($Tl);

$goldenPoints = [];
$silverPoints = [];
$tiles = [];

for ($i = 0; $i < $Gn; $i++) {
    $goldenPoints[$i] = new GoldenPoint(...explode(' ', $content[$fileRow++]));
}
for ($i = 0; $i < $Sm; $i++) {
    $silverPoints[$i] = new SilverPoint(...explode(' ', $content[$fileRow++]));
}
for ($i = 0; $i < $Tl; $i++) {
    $tiles[$i] = new TileType(...explode(' ', $content[$fileRow++]));
}

print_r($goldenPoints);
print_r($silverPoints);
print_r($tiles);
