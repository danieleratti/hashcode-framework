<?php

use Utils\Serializer;
use Utils\Stopwatch;

require_once '../../bootstrap.php';

$nTests = 100000;
$uSecLengthPerCall = 10;

function fxNoCache($i)
{
    global $uSecLengthPerCall;
    usleep($uSecLengthPerCall);
    return $i;
}


Stopwatch::tik('normal');
$db = [];
for ($i = 0; $i < $nTests; $i++)
    $db[$i] = fxNoCache($i);
Stopwatch::tok('normal');


Stopwatch::tik('snapshotted_full');
$method = 'json'; //serialize (instances of classes & complex structures), json (arrays)
if (!($db = Serializer::get('examples-snapshot-serialized', $method))) {
    $db = [];
    for ($i = 0; $i < $nTests; $i++)
        $db[$i] = fxNoCache($i);
    Serializer::set('examples-snapshot-serialized', $db, $method);
}
Stopwatch::tok('snapshotted_full');
// first run: same speed, second+ run: 100X better
// WARNING: it depends on the size!!! (CPU vs Memory) -> it would be useful to snapshot a raw content and decode it by hand


Stopwatch::tik('snapshotted_manual');
$method = 'flat';
if (($flatDb = Serializer::get('examples-snapshot-manual', $method))) {
    $db = [];
    $flatDb = explode("\n", $flatDb);
    foreach ($flatDb as $row) {
        list($in, $out) = explode(";", $row);
        $db[$in] = $out;
    }
} else {
    $db = [];
    for ($i = 0; $i < $nTests; $i++) {
        $out = fxNoCache($i);
        $db[$i] = $out;
        $flatDb[] = "$i;$out";
    }
    $flatDb = implode("\n", $flatDb);
    Serializer::set('examples-snapshot-manual', $flatDb, $method);
}
Stopwatch::tok('snapshotted_manual');


Stopwatch::print();
