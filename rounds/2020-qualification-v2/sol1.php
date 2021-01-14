<?php

use Utils\Collection;
use Utils\Log;

$fileName = 'b';

include 'reader.php';
include_once '../../utils/Analysis/Analyzer.php';

/** @var Books[] $books */
/** @var Libraries[] $libraries */

$analyzer = new Analyzer($fileName, [
    'booksCount' => $books->count(),
    'librariesCount' => $libraries->count()
]);

$analyzer->addDataset('Books', $books->toArray(), ['award', 'inLibrariesCount']);

$analyzer->analyze();

