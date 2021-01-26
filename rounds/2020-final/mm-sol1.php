<?php

$fileName = 'd';

include __DIR__ . '/mm-reader.php';

/** @var int $N_STEPS */
/** @var Cell[] $CELLS */
/** @var Cell[][] $MAP */


/* Posizionamento bracci */

/** @var Arm[] $arms */
$arms = [];

// TODO: sbatti


/* Algo */

/** @var Arm[] $freeArms */
$freeArms = $arms;

// Ordinamento iniziale task
uasort($TASKS, function (Task $t1, Task $t2) {
    return $t1->offsettedScorePerStep < $t2->offsettedScorePerStep;
});

for ($t = 1; $t <= $N_STEPS; $t++) {

    // Libero le celle
    foreach ($CELLS as $cell) {
        if($cell->freeAt === $t) {
            $cell->freeAt = null;
        }
    }

    // Assegno i bracci liberi
    foreach ($freeArms as $arm) {

        // Riordino task
        // uasort($TASKS, ...);

        foreach ($freeArms as $arm) {}
    }

    // Aggiorno lo stato dei bracci
    foreach ($arms as $arm) {

    }
}
