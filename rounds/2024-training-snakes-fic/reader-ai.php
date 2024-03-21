<?php

use Utils\FileManager;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';

/*
The input file is a regular ASCII text file. Each line of the input file is separated
by a single “\n” character (“UNIX-style”). If a line has multiple data, each value
is separated by a single whitespace character. The first row of the input file
will have 3 integer numbers:
• C, indicating the number of columns of the system grid
• R, indicating the number of rows of the system grid
• S, indicating the number of Snakes available to the player
The second row is composed of S integer values, with each one corresponding
to the length of a Snake. Then, R lines follow, with each one consisting of C
values. Each value could either be:
• Vrc, the value of the relevance of the component in that position
• An asterisk (*), representing the presence of a wormhole in that position
 */

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

// Read file
[$columnsCount, $rowsCount, $snakesCount] = explode(' ', $content[0]);
$lengths = explode(' ', $content[1]);
$map = [];
for ($r = 0; $r < $rowsCount; $r++) {
    $map[] = explode(' ', $content[$r + 2]);
}
