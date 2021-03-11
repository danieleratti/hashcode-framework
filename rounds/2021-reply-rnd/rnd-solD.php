<?php


use Utils\Collection;
use Utils\FileManager;
use Utils\Log;

$fileName = 'b';

include 'rnd-reader.php';

/** @var Collection|Building[] $BUILDINGS*/
/** @var Collection|Antenna[] $ANTENNAS*/
/** @var MAP $MAP */
/** @var FileManager $fileManager */
/** @var Collection|Square[] $clusters */
$clusters= collect();
$sortedBuildings = $BUILDINGS->sortByDesc(function($building){
    return ( $building->cell->y.'.' .$building->cell->x);
});

$y=0;
foreach ($sortedBuildings as $k =>$building){
    Log::out($y . 'cluster trovati '.$clusters->count());
    if($clusters->count()===0)
    {
        $upperLeft= $building->cell;
        $upperRight= $building->cell;
        $lowerLeft= $building->cell;
        $lowerRight= $building->cell;
        $newCluster= new Square($upperLeft, $upperRight, $lowerLeft, $lowerRight);
        $clusters->push($newCluster);
    }
    else{
        $found=false;
        foreach ($clusters as $keyCluster =>$cluster){
            if($cluster->lowerLeft->x- $building->cell->x<50 && $cluster->lowerLeft->y-$building->cell-y<20)
            {
                $found=true;
                $clusters[$keyCluster]->lowerLeft = $building->cell;
            }

            if($cluster->upperRight->y- $building->cell->y<50 && $cluster->upperRight->x-$building->cell->x<20)
            {
                $found=true;
                $clusters[$keyCluster]->upperRight = $building->cell;
            }

            if($cluster->upperLeft->x- $building->cell->x<50  )
            {
                $found=true;
                $clusters[$keyCluster]->upperLeft = $building->cell;
            }
        }

        if(!$found){
            $upperLeft= $building->cell;
            $upperRight= $building->cell;
            $lowerLeft= $building->cell;
            $lowerRight= $building->cell;
            $newCluster= new Square($upperLeft, $upperRight, $lowerLeft, $lowerRight);
            $clusters->push($newCluster);
        }
        $y=$building->cell->y;
    }

}

/*$sortedXbuildings= $BUILDINGS->sortByDesc(function($building){
    return $building->cell->x;
});*/

$test='123';



