<?php

use Utils\FileManager;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';

class MapManager
{
    public array $map;

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

    public function putSnake(int $r, int $c): void
    {
        if ($this->hasSnake($r, $c)) {
            throw new Error("Snake already here [$r, $c].");
        }
        $this->map[$r][$c] = '.';
    }

    public function hasSnake(int $r, int $c): bool
    {
        return $this->map[$r][$c] === '.';
    }

    public function hasSnakeIf(string $direction, array $cell): bool
    {
        $cell = $this->getNextCellForDirection($direction, $cell);
        return $this->hasSnake(...$cell);
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
     * @param Snake[] $snakes
     * @return void
     */
    public function visualizeWithSnakes(array $snakes): void
    {

    }
}

class Snake
{
    public array $path = [];
    public array $commands = [];
    public array $head = [];
    public int $currentLength = 0;

    public function __construct(
        public int                  $id,
        public int                  $length,
        private readonly MapManager $mapManager
    )
    {
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