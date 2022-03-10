<?php

use Utils\FileManager;

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

require_once '../../bootstrap.php';

class Hero
{
    /** @var int */
    public int $stamina;
    /** @var int */
    public int $fragments = 0;
    /** @var int */
    public int $maxStamina;
    /** @var int[] */
    public array $staminaRecovering = [];

    public function __construct(int $maxStamina, int $initialStamina)
    {
        $this->stamina = $initialStamina;
        $this->maxStamina = $maxStamina;
    }

    public function battleDemon(Demon $d, int $t)
    {
        $this->stamina -= $d->staminaRequired;
        $this->fragments += $d->getScoreAtTime($t);
        $this->staminaRecovering[$t + $d->staminaRecoverTime] = $d->staminaRecoverAmount;
    }

    public function recoverStamina(int $t)
    {
        if(isset($this->staminaRecovering[$t])) {
            $this->stamina += $this->staminaRecovering[$t];
            if($this->stamina > $this->maxStamina) {
                $this->stamina = $this->maxStamina;
            }
        }
    }

    public function canDefeat(Demon $d): bool
    {
        return $d->staminaRequired <= $this->stamina;
    }
}

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

    /** @var int */
    public int $totalFragments = 0;
    /** @var float */
    public float $weightedReward = 0;
    /** @var float */
    public float $value = 0;

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

    public function calculateTotalFragments(int $t = 0)
    {
        global $turnsNumber;
        $this->totalFragments = 0;
        $this->weightedReward = 0;
        foreach ($this->fragmentsRewardDetails as $i => $r) {
            if($t + $i < $turnsNumber) {
                $this->totalFragments += $r;
                $this->weightedReward += $r / $i;
            }
        }
    }

    public function calculateValue(int $t = 0)
    {
        //$this->value = ($this->staminaRecoverAmount - $this->staminaRequired);
        $this->value = log($this->weightedReward) * ($this->staminaRecoverAmount - $this->staminaRequired) / pow($this->staminaRecoverTime, 0.5);
        //$this->value = 1 / (1 + pow(M_E, - $this->staminaRecoverAmount + $this->staminaRequired)) / pow($this->staminaRecoverTime, 0.5);
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
    $d->calculateTotalFragments();
    $demons[$i] = $d;
}
