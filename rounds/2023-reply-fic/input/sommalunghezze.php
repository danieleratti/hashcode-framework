<?php

$files = [
	"b_01-chilling-cat.txt",
	"c_02-swarming-ant.txt",
	"d_03-input-anti-greedy.txt",
	"e_04-input-low-points.txt",
	"f_05-input-opposite-points-holes.txt"
];

foreach($files as $file) {
	$f = file_get_contents($file);
	$f = explode("\n", $f);
	$area = explode(" ", $f[0]);
	$area = $area[0]*$area[1];
	$nserp = count(explode(" ", $f[1]));
	$ltot = array_sum(explode(" ", $f[1]));
	echo "$file => NSerpenti=".$nserp." // LTotSerpenti=".$ltot." // Area=".$area." // coverage=".($ltot/$area)."\n\n";
	
	$lunghezze = explode(" ", $f[1]);
	sort($lunghezze);
	//echo implode(" ", $lunghezze)."\n\n";
	
}
