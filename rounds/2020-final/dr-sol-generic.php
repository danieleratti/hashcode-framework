<?php

use Utils\Autoupload;

$fileName = 'd';

include 'dr-reader.php';

/** @var MountPoint[] $MOUNT_POINTS */
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

