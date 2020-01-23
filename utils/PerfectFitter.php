<?php

namespace Utils;

/*
 * Si può migliorare aggiungendo
 * - permettere con un opzione di fittare anche sforando lo spazio
 * - possa fittare degli oggetti utilizzando una loro proprietà
 * - skippare test già fatti (difficile)
 */

class PerfectFitter
{
    private $space;
    private $orderedItems;
    private $orderedKeys;

    private $startedAt;

    private $bestSolutionFounded;
    private $bestSolutionSumFounded;

    public function __construct(array $items, $space)
    {
        $this->space = $space;
        $this->orderedItems = $items;
        arsort($this->orderedItems);
        $this->orderedKeys = array_keys($this->orderedItems);
    }

    /**
     * restituisce l'elenco delle chiavi dell'arry di input che fittano lo spazio
     * @param array $options
     * @return array
     */
    public function fit($options = null)
    {
        $this->startedAt = microtime(true);
        $message = null;

        try {
            $itemsSum = array_sum($this->orderedItems);

            if ($itemsSum < $this->space) {
                $this->bestSolutionFounded = $this->orderedKeys;
                $this->bestSolutionSumFounded = $itemsSum;
                throw new \Exception('too much space');
            }

            $this->tryAllSolutions([], 0, 0, $options);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $founded = !!$this->bestSolutionSumFounded;
        $sum = $this->bestSolutionSumFounded;
        $delta = $this->space - $sum;

        return [
            'founded' => $founded,
            'solution' => $this->bestSolutionFounded,
            'sum' => $this->bestSolutionSumFounded,
            'delta' => $delta,
            'executionTime' => microtime(true) - $this->startedAt,
            'message' => $message
        ];
    }

    private function tryAllSolutions($solution, $i, $sum, $options)
    {
        if (isset($options['maxTime']) && (microtime(true) - $this->startedAt) > isset($options['maxTime']))
            throw new \Exception('time expired');
        if (isset($options['acceptedDelta']) && $sum >= $options['acceptedDelta'])
            throw new \Exception('accepted delta');

        for (; $i < count($this->orderedKeys); $i++) {
            $newSum = $sum;

            $key = $this->orderedKeys[$i];
            $value = $this->orderedItems[$key];

            if ($newSum + $value > $this->space)
                continue;

            $newSum += $value;

            $newSolution = $solution;
            $newSolution[] = $key;

            if (!$this->bestSolutionSumFounded || $newSum > $this->bestSolutionSumFounded) {
                $this->bestSolutionFounded = $newSolution;
                $this->bestSolutionSumFounded = $newSum;

                if ($newSum == $this->space)
                    throw new \Exception('perfect');
            }

            $this->tryAllSolutions($newSolution, $i + 1, $newSum, $options);
        }
    }
}
