<?php


use Utils\FileManager;
use Utils\Log;

$fileName = 'b';

include 'reader.php';

/** @var ProjectManager[] $PROJECTMANAGERS */
/** @var Developer[] $DEVELOPERS */
/** @var FileManager $fileManager */
/** @var Map $MAP */

/** @var Company[] $COMPANIES */

foreach ($COMPANIES as $k =>$company){
    usort($company->inDevelopers,  );
}