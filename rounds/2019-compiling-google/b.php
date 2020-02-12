<?php

$fileName = 'b';

require_once 'reader.php';

/** @var TargetFile[] $targets */

print_r($targets[0]->file->dependencies);
