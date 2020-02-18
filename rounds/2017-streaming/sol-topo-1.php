<?php

$fileName = 'a';

require_once 'reader.php';

/** @var Tupla $first */
while($first = $tuple->sortByDesc('localScore')->first()) {
    putVideoInCache($first->requestId, $first->videoId, $first->cacheId);
}
