<?php

use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualStandard;

$fileName = 'b';

include 'dr-reader.php';

//assumption: non ci sono mount point ai bordi, quindi nessun rischio di crash
//dir è -1 (sx) / 1 (dx)
//bin è 0 (binario basso) / 1 (binario alto)

// istanzio il visual
$visualStandard = new VisualStandard($H, $W);
foreach ($MOUNT_POINTS as $mp)
    $visualStandard->setPixel($mp->y, $mp->x, Colors::blue9);
foreach ($ASSEMBLY_POINTS as $ap)
    $visualStandard->setPixel($ap->y, $ap->x, Colors::red9);
$visualStandard->save($fileName);

// aggiungo un punto coperto dal braccio
function coverPointWithArm()
{
    global $SCORE;
    global $STEPS_DONE;
    global $coverPointWithArmCounter;
    global $visualStandard;
    global $armX;
    global $armY;
    global $XY_ASSEMBLY_POINTS;

    $STEPS_DONE++;

    if($XY_ASSEMBLY_POINTS[$armX][$armY]) {
        foreach($XY_ASSEMBLY_POINTS[$armX][$armY]->singleTasks as $task) {
            $SCORE += $task->score;
        }
    }
    Log::out("X=$armX Y=$armY SCORE=$SCORE STEPS_DONE=$STEPS_DONE", 1);

    $visualStandard->setPixel($armY, $armX, Colors::orange5);
    $coverPointWithArmCounter++;
    if ($coverPointWithArmCounter % 100 == 0)
        refreshImage();
}

function refreshImage()
{
    global $visualStandard, $fileName;
    $visualStandard->save($fileName.'-out');
}

$startX = 73;
$startY = 1;

$armX = $startX;
$armY = $startY;

$startedTrainAlgo = false;
$dir = 1;
$bin = 0;

while (true) {
    if (!$startedTrainAlgo) {
        if ($armX == $W - 1) { //scontrato contro muro destro
            $dir = -1;
            $bin = 1;
            $armY -= 1;
            coverPointWithArm();
        }
        $armX += $dir;
        coverPointWithArm();
        if ($dir == -1 && $armX == $startX) {
            $startedTrainAlgo = true;
            $bin = 1; // not needed, but to clarify
            $dir = -1; // not needed, but to clarify
        }
    } else {
        //CICLO fino a che non finisce tutto
        // IF davanti a me c'è il muro (nella dir in cui sto andando):
        if (($dir == -1 && $armX == 0) || ($dir == 1 && $armX == $W - 1)) {
            if($armY >= $H-2) {
                Log::out("Finished!");
                break;
            }
            //   vado giù di (1+bin) [mi trovo sempre nel binario alto praticamente]
            $downSteps = (1 + $bin);
            for ($i = 0; $i < $downSteps; $i++) {
                $armY += 1;
                coverPointWithArm();
            }
            //   bin = 1
            //   dir = -dir
            $bin = 1;
            $dir = -$dir;
        } else {
            // ELSE:
            //   vado avanti orizzontalmente (x) di "dir"
            $armX += $dir;
            coverPointWithArm();
        }

        // se sull'altro binario (più alto o più in basso) c'è un assembly point, salgo/scendo e bin = 1-bin
        $otherBinDeltaY = $bin==1?1:-1;
        $otherBinY = $armY + $otherBinDeltaY;
        if($XY_ASSEMBLY_POINTS[$armX][$otherBinY] || $MOUNT_POINTS->where('x', $armX+$dir)->where('y', $armY)->first()) {
            $bin = 1-$bin;
            $armY = $otherBinY;
            coverPointWithArm();
        }
    }
}
refreshImage();

//tutto a destra fino muro
//up
//tutto sx fino startX
//algoritmo trenino con dir=-1 (sx), bin=1(alto)

