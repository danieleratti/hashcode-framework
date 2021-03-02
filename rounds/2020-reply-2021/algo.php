<?php

include_once 'reader.php';

/**
 * Ritorna lo score della coppia di persone
 */
function getCoupleScore($a, $b)
{
}

/**
 * ritorna la prima cella ancora da riempire, false se ha finito
 * @return Cell
 */
function generateSeed()
{
}

/**
 * @param Cell $edge
 * @return Replier
 */
function getBestReplier($edge)
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
