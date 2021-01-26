<?php

$fileName = 'b';

include 'dr-reader.php';

$startX = 73;
$startY = 1;

//assumption: non ci sono mount point ai bordi, quindi nessun rischio di crash
//dir è -1 (sx) / 1 (dx)
//bin è 0 (binario basso) / 1 (binario alto)

//tutto a destra fino muro
//up
//tutto sx fino startX
//algoritmo trenino con dir=-1 (sx), bin=1(alto)

//CICLO fino a che non finisce tutto
// IF davanti a me c'è il muro (nella dir in cui sto andando):
//   vado giù di (1+bin) [mi trovo sempre nel binario alto praticamente]
//   bin = 1
//   dir = -dir
// ELSE:
//   vado avanti orizzontalmente (x) di "dir"
//
// se sull'altro binario (più alto o più in basso) c'è un assembly point, salgo/scendo e bin = 1-bin


die();
