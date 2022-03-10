<?php

/** @var string */
global $fileName;

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

use Utils\FileManager;

require_once '../../bootstrap.php';

class Demon
{
    /** @var int */
    public int $id;
    /** @var int */
    public int $staminaRequired;
    /** @var int */
    public int $staminaRecoverTime;
    /** @var int */
    public int $staminaRecoverAmount;
    /** @var int */
    public int $fragmentsRewardTime;
    /** @var int[] */
    public array $fragmentsRewardDetails = [];

    public function getScoreAtTime(int $t): int
    {
        global $turnsNumber;
        $i = 0;
        $score = 0;
        while ($i < $this->fragmentsRewardTime && $t + $i < $turnsNumber) {
            $score += $this->fragmentsRewardDetails[$i];
            $i++;
        }
        return $score;
    }
}

/* Reading the input */
$fileManager = new FileManager($fileName);
$content = explode("\n", $fileManager->get());

$fileRow = 0;

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
    $demons[$i] = $d;
}
