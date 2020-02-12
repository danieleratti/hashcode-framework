<?php

$fileName = 'a';

require_once 'reader.php';

$output = "7
c1 1
c0 0
c3 1
c2 0
c2 1
c4 0
c5 1";

verifyOutput($output);
$fileManager->output($output);
