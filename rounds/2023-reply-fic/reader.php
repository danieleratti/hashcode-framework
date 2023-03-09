<?php

use Utils\FileManager;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';

class Snake
{
    /** @var int $id */
    public int $id;
    /** @var int $length */
    public int $length;

    public function __construct(
        int $id,
        int $length
    )
    {
        $this->id = $id;
        $this->length = $length;
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

print_r($snakes);
print_r($map);
unset($content);