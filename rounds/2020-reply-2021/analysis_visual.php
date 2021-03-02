<?php

use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'a';

include 'reader.php';

$visualStandard = new VisualStandard($rows, $cols);

$visualStandard->save('visualEfficiency_' . $fileName);
