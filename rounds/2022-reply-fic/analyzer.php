<?php

use Utils\Analysis\Analyzer;
use Utils\FileManager;
use Utils\Log;
use Utils\Visual\Colors;
use Utils\Visual\VisualGradient;
use Utils\Visual\VisualStandard;

/** @var string */
global $fileName;
/** @var FileManager */
global $fileManager;

/** @var Demon[] */
global $demons;

/** @var int */
global $initialStamina;
/** @var int */
global $maxStamina;
/** @var int */
global $turnsNumber;
/** @var int */
global $demonsCount;

$fileName = 'b';

/* Reader */
include_once 'mm-reader.php';

$analyzer = new Analyzer($fileName, [
    'demons_count' => $demonsCount,
    'turns' => $turnsNumber,
    'max_stamina' => $maxStamina,
    'initial_stamina' => $initialStamina,
]);

$analyzer->addDataset('demons', $demons, ['staminaRequired', 'staminaRecoverTime', 'staminaRecoverAmount', 'fragmentsRewardTime', 'totalFragments']);

$analyzer->analyze();

die();
