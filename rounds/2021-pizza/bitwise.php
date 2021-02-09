<?php

//https://stackoverflow.com/questions/864058/how-can-i-have-a-64-bit-integer-in-php

$dec1 = bindec("100000");
$dec2 = bindec("111111");
//printf("%'06b", 0b110101 & 0b011001); // 010001
//printf("%'32b", $dec1 & $dec2);

/*
$t = microtime(true);
for($i=0;$i<1000*1000*1000;$i++) {
	$x = ($dec1 & $dec2);
}
$t = microtime(true)-$t;
echo "T=$t";
die();
*/

$cmp = str_pad(decbin($dec1 & $dec2), 6, "0", STR_PAD_RIGHT);

echo "CMP = $cmp";
