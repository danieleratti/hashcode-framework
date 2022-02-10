<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager $fileManager */
global $fileManager;
/** @var Client[] $clients */
global $clients;
/** @var Ingredient[] $ingredients */
global $ingredients;
/** @var int $clientsNumber */
global $clientsNumber;

$analyzer = new Analyzer($fileName, [
    'clients_number' => $clientsNumber
]);

$analyzer->addDataset('clients', $clients, ['likesAsString', 'dislikesAsString']);

$analyzer->analyze();
