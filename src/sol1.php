<?php

require_once '../bootstrap.php';

$fileManager = new \Src\Utils\FileManager('a');

$fileManager->output($fileManager->get());
