<?php

$fileName = 'a';

require_once './classes.php';

$grid = new Grid();
$grid->placeRouter(4, 11);
$grid->printSolution();
$grid->outputSolution();
