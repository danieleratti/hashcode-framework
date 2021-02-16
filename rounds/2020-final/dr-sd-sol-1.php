<?php

use Utils\Autoupload;
use Utils\Collection;

$fileName = 'a';

include 'dr-reader.php';


class TCube
{
    public $T;

    // istruzioni per eseguire un task N, e posizionarsi al nodo più vicino allo startingPoint del task N+1
    function verify($startX, $startY, $startT, $instructions, $robotId)
    {

    }

    function execute($startX, $startY, $startT, $instructions, $robotId)
    {

    }
}


/** @var Collection|MountPoint[] $MOUNT_POINTS */
/** @var Collection|AssemblyPoint[] $ASSEMBLY_POINTS */
/** @var Collection|Task[] $TASKS */
/** @var int $N_STEPS */

print_r(Autoupload::submission('b', 'test123', 'abc'));
die();


$TCUBE = []; //Cubo del tempo [T][x][y] =  1 se occupato per sempre (mountpoint), 2 se occupato temporaneamente

// Aggiungo i mount points al cubo del tempo affinché sia sempre occupato per ogni T
foreach ($MOUNT_POINTS as $mp) {
    for ($i = 0; $i < $N_STEPS; $i++) {
        $TCUBE[$i][$mp->x][$mp->y] = 1;
    }
}

