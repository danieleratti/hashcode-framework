<?php

use Utils\FileManager;

/** @var string $fileName */
global $fileName;
/** @var FileManager $fileManager */
global $fileManager;

require_once '../../bootstrap.php';


/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;

/*
[$initialStamina, $maxStamina, $turnsNumber, $demonsCount] = explode(' ', $content[$fileRow++]);
$initialStamina = (int)$initialStamina;
$maxStamina = (int)$maxStamina;
$turnsNumber = (int)$turnsNumber;
$demonsCount = (int)$demonsCount;

$demons = [];

for ($i = 0; $i < $demonsCount; $i++) {
    [$requiredStamina, $staminaRecoverTime, $staminaRecoverAmount, $fragmentsRewardTime, $fragmentsDetails] = explode(' ', $content[$fileRow++], 5);
    $d = new Demon();
    $d->id = $i;
    $d->staminaRequired = (int)$requiredStamina;
    $d->staminaRecoverTime = (int)$staminaRecoverTime;
    $d->staminaRecoverAmount = (int)$staminaRecoverAmount;
    $d->fragmentsRewardTime = (int)$fragmentsRewardTime;
    $fragmentsDetails = explode(' ', $fragmentsDetails);
    foreach ($fragmentsDetails as $k => &$f) {
        $f = (int)$f;
    }
    $d->fragmentsRewardDetails = $fragmentsDetails;
    $d->calculateTotalFragments();
    $demons[$i] = $d;
}
*/