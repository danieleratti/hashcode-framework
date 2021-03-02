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
 * ritorna la prima cella ancora da riempire, null se ha finito
 * @return Cell|null
 */
function generateSeed()
{
    global $freeDevelopers, $freeManagers, $freeDevelopersCells, $freeManagersCells;

    if (count($freeDevelopers) && count($freeDevelopersCells)) {
        return array_pop($freeDevelopersCells);
    }

    if (count($freeManagers) && count($freeManagersCells)) {
        return array_pop($freeManagersCells);
    }

    return null;
}

/**
 * @param Cell $edge
 * @return Replier
 */
function getBestReplier(Cell $edge)
{
    $bestRepliers = [];
    foreach ($edge->nears as $near) {
        if ($near->replier) {
            $bestRepliers = array_merge($bestRepliers, $near->type === 'M' ? $near->replier->bestManagers : $near->replier->bestDevelopers);
        }
    }
    $bestScore = -1;
    $bestReplier = null;
    foreach ($bestRepliers as $replier) {
        $score = getCellScore($edge, $replier);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestReplier = $replier;
        }
    }
    return $bestReplier;
}

/**
 * ritorna tutte le celle adiacenti non ancora considerate
 * @param Cell $cell
 * @return Cell[]
 */
function getCellEdges(Cell $cell)
{
    return array_filter($cell->nears, function (Cell $c) {
        return !$c->toBeChecked;
    });
}

/**
 * @param Cell $cell
 * @return int
 */
function getCellScore(Cell $cell, Replier $replier)
{
    return array_reduce($cell->nears, function (int $score, Cell $c) use ($cell, $replier) {
        return $score + getCoupleScore($replier, $c->replier);
    }, 0);
}

$score = 0;

// Mentre esistono ancora scrivanie da riempire o developer o manager
while ($seed = generateSeed()) {
    $edges = [$seed];

    while (count($edges)) {
        $edge = array_shift($edges);
        $replier = getBestReplier($edge);

        if (!$replier) {
            $edge->setEmpty();
            continue;
        }

        $edge->sit($replier);
        $score += getCellScore($edge);

        foreach (getCellEdges($edge) as $cell) {
            $edges[] = $cell;
        }
    }
}
