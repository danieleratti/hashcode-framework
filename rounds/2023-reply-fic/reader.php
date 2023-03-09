<?php

use Utils\FileManager;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';

class Snake
{
    public array $path = [];
    public array $commands = [];
    public array $head = [];
    public $currentLength = 0;

    public function __construct(
        public int $id,
        public int $length
    )
    {
    }

    public function setInitialHead(int $r, int $c): void
    {
        $this->head = [$r, $c];
        $this->path = [[$r, $c]];
        $this->currentLength = 1;
    }

    public function addDirectionCommand(string $direction): void
    {
        $this->commands[] = $direction;
        $translate = match ($direction) {
            'U' => [-1, 0],
            'D' => [1, 0],
            'L' => [0, -1],
            'R' => [0, 1],
        };
        $this->head = [$this->head[0] + $translate[0], $this->head[1] + $translate[1]];
        $this->path[] = $this->head;
        $this->currentLength++;
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
        $output = $this->head[1] . ' ' . $this->head[0];
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

/** @var Snake[] $snakes */
$snakes = [];
$lengths = explode(' ', $content[$fileRow++]);
$i = 0;
foreach ($lengths as $l) {
    $snakes[$i] = new Snake($i, $l);
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

//print_r($snakes);
//print_r($map);
unset($content);