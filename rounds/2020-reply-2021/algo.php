<?php

include_once __DIR__ . '/reader.php';

/**
 * Ritorna lo score della coppia di persone
 * @return int
 */
function getCoupleScore(Replier $a, Replier $b)
{
    $score = 0;

    if ($a->company === $b->company) {
        $score += $a->bonus * $b->bonus;
    }

    if ($a instanceof Developer && $b instanceof Developer) {
        $commonSkillsNumber = count(array_intersect($a->skills, $b->skills));
        $uncommonSkillsNumber = count(array_unique(array_merge($a->skills, $b->skills))) - $commonSkillsNumber;

        $score += $commonSkillsNumber * $uncommonSkillsNumber;
    }

    return $score;
}

/**
 * ritorna la prima cella ancora da riempire, false se ha finito
 * @return Cell
 */
function generateSeed()
{
    return null;
}

/**
 * @param Cell $edge
 * @return Replier
 */
function getBestReplier(Cell $edge)
{
    return null;
}

/**
 * ritorna tutte le celle adiacenti non ancora riempite
 * @param Cell $cell
 * @return Cell[]
 */
function getCellEdges($cell)
{
    return [];
}

/**
 * @param Cell $cell
 * @return int
 */
function getCellScore($cell)
{
    return 0;
}

$score = 0;

// Mentre esistono ancora scrivanie da riempire o developer o manager
while (false) {
    // Generiamo il seed
    $seed = generateSeed();
    $edges = [$seed];

    while (count($edges)) {
        $edge = array_shift($edges);
        $replier = getBestReplier($edge);
        $edge->sit($replier);
        $score += getCellScore($edge);

        foreach (getCellEdges($edge) as $cell) {
            $edges[] = $cell;
        }
    }
}
