<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;

global $fileName;
/** @var FileManager */
global $fileManager;
/** @var Client[] */
global $clients;
/** @var Ingredient[] */
global $ingredients;
/** @var int $clientsNumber */
global $clientsNumber;

$analyzer = new Analyzer($fileName, [
    'clients_number' => $clientsNumber
]);

$analyzer->addDataset('clients', $clients, ['likesAsString', 'dislikesAsString']);

$analyzer->analyze();
