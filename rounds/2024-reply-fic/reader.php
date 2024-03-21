<?php

global $fileName;
global $_visualyze;
global $_analyze;

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Visual\VisualGradient;

require_once '../../bootstrap.php';

class GoldenPoint
{
    public int $r;
    public int $c;

    public function __construct(int $r, int $c)
    {
        $this->r = $r;
        $this->c = $c;
    }
}

class SilverPoint
{
    public int $r;
    public int $c;
    public int $score;

    public function __construct(int $r, int $c, int $score)
    {
        $this->r = $r;
        $this->c = $c;
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

class MapManager
{
    public string $filename;
    public array $map;
    public int $rowsCount;
    public int $columnsCount;

    public function __construct(string $filename, int $rowsCount, int $columnsCount)
    {
        $this->filename = $filename;
        $this->rowsCount = $rowsCount;
        $this->columnsCount = $columnsCount;
        $this->map = array_fill(0, $rowsCount, array_fill(0, $columnsCount, null));
    }

    public function setGoldenPoint(GoldenPoint $point): void
    {
        $this->map[$point->r][$point->c] = $point;
    }

    public function setSilverPoint(SilverPoint $point): void
    {
        $this->map[$point->r][$point->c] = $point;
    }

    public function visualize(): void
    {
        $visualGradient = new VisualGradient($this->rowsCount, $this->columnsCount);

        $maxAbsScore = 0;

        for ($c = 0; $c < $this->columnsCount; $c++) {
            for ($r = 0; $r < $this->rowsCount; $r++) {
                $v = $this->map[$r][$c];
                if ($v instanceof SilverPoint) {
                    $maxAbsScore = max($maxAbsScore, $v->score);
                }
            }
        }

        for ($c = 0; $c < $this->columnsCount; $c++) {
            for ($r = 0; $r < $this->rowsCount; $r++) {
                $v = $this->map[$r][$c];
                if ($v instanceof GoldenPoint) {
                    $color = [0xff, 0x00, 0x00];
                } elseif ($v instanceof SilverPoint) {
                    $color = [0x00, 0xff - ($v->score / $maxAbsScore * 0x7f), 0x00];
                } else {
                    $color = [0x00, 0x00, 0x00];
                }
                $visualGradient->setCustomPixel($r, $c, ...$color);
            }
        }

        $visualGradient->save($this->filename);
    }
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());
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
$tileTypes = [];

for ($i = 0; $i < $Gn; $i++) {
    [$c, $r] = explode(' ', $content[$fileRow++]);
    $goldenPoints[$i] = new GoldenPoint($r, $c);
}
for ($i = 0; $i < $Sm; $i++) {
    [$c, $r, $score] = explode(' ', $content[$fileRow++]);
    $silverPoints[$i] = new SilverPoint($r, $c, $score);
}
for ($i = 0; $i < $Tl; $i++) {
    $tileTypes[$i] = new TileType(...explode(' ', $content[$fileRow++]));
}

//print_r($goldenPoints);
//print_r($silverPoints);
//print_r($tileTypes);

$mapManager = new MapManager($fileName, $H, $W);
foreach ($goldenPoints as $gp) {
    $mapManager->setGoldenPoint($gp);
}
foreach ($silverPoints as $sp) {
    $mapManager->setSilverPoint($sp);
}

// Visualize
if ($_visualyze) {
    $mapManager->visualize();
}

// Analyze
if ($_analyze) {
    $analyzer = new Analyzer($fileName, [
        'rows' => $H,
        'columns' => $W,
        'golden_points' => $Gn,
        'silver_points' => $Sm,
        'tiles_types' => $Tl,
    ]);
    $analyzer->addDataset('golden_points', $goldenPoints, []);
    $analyzer->addDataset('silver_points', $silverPoints, ['score']);
    $analyzer->addDataset('tile_types', $tileTypes, ['cost', 'count']);
    $analyzer->analyze();
}