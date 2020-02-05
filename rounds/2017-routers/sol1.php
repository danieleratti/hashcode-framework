<?php

$fileName = 'b';

require_once './classes.php';

$grid = new Grid();
$grid->placeRouter(32, 60);
$grid->printSolution();
$grid->outputSolution();
