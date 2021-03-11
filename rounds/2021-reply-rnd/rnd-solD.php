<?php


use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'd';

include 'rnd-reader.php';

/** @var Collection|Building[] $BUILDINGS*/
/** @var Collection|Antenna[] $ANTENNAS*/
/** @var MAP $MAP */
/** @var FileManager $fileManager */


$sortedYbuildings = $BUILDINGS->sortByDesc(function($building){
    return ( $building->cell->y.'.' .$building->cell->x);
});

/*$sortedXbuildings= $BUILDINGS->sortByDesc(function($building){
    return $building->cell->x;
});*/

$test='123';



