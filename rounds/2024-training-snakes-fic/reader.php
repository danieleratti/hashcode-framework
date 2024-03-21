<?php

use Utils\FileManager;
use Utils\Visual\VisualGradient;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';

class MapManager
{
    public const SNAKE_LAYER = 0;
    public const BIGSNAKE_LAYER = 1;

    public array $map;
    public array $layers = [];

    public function __construct(
        public int $rowsCount,
        public int $columnsCount,
    )
    {
    }

    public function setMap(array $map): void
    {
        $this->map = $map;
    }

    public function putSnake(int $r, int $c, int $layer = self::SNAKE_LAYER): void
    {
        if ($this->hasSnake($r, $c, $layer)) {
            throw new Error("Snake already here [$r, $c].");
        }
        $this->layers[$layer][$r][$c] = '.';
    }

    public function hasSnake(int $r, int $c, int $layer = self::SNAKE_LAYER): bool
    {
        return $this->layers[$layer][$r][$c] === '.';
    }

    public function hasSnakeIf(string $direction, array $cell, int $layer = self::SNAKE_LAYER): bool
    {
        $cell = $this->getNextCellForDirection($direction, $cell);
        return $this->hasSnake($cell[0], $cell[1], $layer);
    }

    public function getNextCellForDirection(string $direction, array $cell): array|false
    {
        switch ($direction) {
            case 'U':
                $cell[0]--;
                if ($cell[0] < 0) {
                    $cell[0] = $this->rowsCount - 1;
                }
                break;
            case 'D':
                $cell[0]++;
                if ($cell[0] >= $this->rowsCount) {
                    $cell[0] = 0;
                }
                break;
            case 'L':
                $cell[1]--;
                if ($cell[1] < 0) {
                    $cell[1] = $this->columnsCount - 1;
                }
                break;
            case 'R':
                $cell[1]++;
                if ($cell[1] >= $this->columnsCount) {
                    $cell[1] = 0;
                }
                break;
            default:
                throw new Error('Unexpected command.');
        }
        return $cell;
    }

    /**
     * @param int $layer
     * @return void
     */
    public function visualizeWithSnakes(string $filename, int $layer = self::SNAKE_LAYER): void
    {
        $visualGradient = new VisualGradient($this->rowsCount, $this->columnsCount);

        $maxAbsScore = 0;

        for ($c = 0; $c < $this->columnsCount; $c++) {
            for ($r = 0; $r < $this->rowsCount; $r++) {
                $maxAbsScore = max(abs($maxAbsScore), $this->map[$r][$c]);
            }
        }

        for ($c = 0; $c < $this->columnsCount; $c++) {
            for ($r = 0; $r < $this->rowsCount; $r++) {
                $v = $this->map[$r][$c];
                $s = $this->layers[$layer][$r][$c];
                if ($s === '.') {
                    $color = [0x00, 0x00, 0xff];
                } elseif ($v === '*') {
                    $color = [0xff, 0xff, 0xff];
                } elseif ($v > 0) {
                    $color = [0x00, $v / $maxAbsScore * 0xff, 0x00];
                } elseif ($v < 0) {
                    $color = [-$v / $maxAbsScore * 0xff, 0x00, 0x00];
                } else {
                    $color = [0x00, 0x00, 0x00];
                }
                $visualGradient->setCustomPixel($r, $c, ...$color);
            }
        }

        $visualGradient->save($filename);
    }

    public function __clone(): void
    {
        $this->map = array_map(fn($row) => array_map(fn($cell) => $cell, $row), $this->map);
    }
}

class Snake
{
    public array $path = [];
    public array $commands = [];
    public array $head = [];
    public int $currentLength = 0;
    public int $id;
    public int $length;
    private readonly MapManager $mapManager;

    public function __construct(
        int        $id,
        int        $length,
        MapManager $mapManager
    )
    {
        $this->id = $id;
        $this->length = $length;
        $this->mapManager = $mapManager;
    }

    public function setInitialHead(int $r, int $c): void
    {
        $this->head = [$r, $c];
        $this->path = [[$r, $c]];
        $this->currentLength = 1;
        $this->mapManager->putSnake($r, $c);
    }

    public function addDirectionCommand(string $direction, bool $autoTeleport = false): void
    {
        $this->commands[] = $direction;
        $this->head = $this->mapManager->getNextCellForDirection($direction, $this->head);
        $this->path[] = $this->head;
        $this->currentLength++;

        if ($autoTeleport && $this->mapManager->map[$this->head[0]][$this->head[1]] === '*') {
            $this->addTeleportCommand(...$this->head);
        } else {
            $this->mapManager->putSnake(...$this->head);
        }
    }

    public function addTeleportCommand(int $r, int $c): void
    {
        $this->commands[] = [$r, $c];
        $this->head = [$r, $c];
    }

    public function getRemainingLength(): int
    {
        return $this->length - $this->currentLength;
    }

    public function getOutputPath(): string
    {
        $output = $this->path[0][1] . ' ' . $this->path[0][0];
        foreach ($this->commands as $c) {
            if (is_array($c)) {
                $output .= ' ' . $c[1] . ' ' . $c[0];
            } else {
                $output .= ' ' . $c;
            }
        }
        return $output;
    }
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;

/** @var int $rowsCount */
/** @var int $columnsCount */
/** @var int $snakesCount */
[$columnsCount, $rowsCount, $snakesCount] = explode(' ', $content[$fileRow++]);
$rowsCount = (int)trim($rowsCount);
$columnsCount = (int)trim($columnsCount);
$snakesCount = (int)trim($snakesCount);

$mapManager = new MapManager($rowsCount, $columnsCount);

/** @var Snake[] $snakes */
$snakes = [];
$lengths = explode(' ', $content[$fileRow++]);
$i = 0;
foreach ($lengths as $l) {
    $snakes[$i] = new Snake($i, $l, $mapManager);
    $i++;
}

/** @var array[] $map */
$map = [];
for ($r = 0; $r < $rowsCount; $r++) {
    $columns = explode(' ', $content[$fileRow++]);
    foreach ($columns as $c => $v) {
        $map[$r][$c] = $v === '*' ? '*' : (int)trim($v);
    }
}

$mapManager->setMap($map);
$map = &$mapManager->map;

//print_r($snakes);
//print_r($map);
unset($content);