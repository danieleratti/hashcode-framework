<?php

use Utils\Log;

$fileName='b';
include 'sl-reader.php';

/** @var Pizza[] $pizzas */
/** @var Ingredient[] $ingredients */
/** @var int $teamFour */
/** @var int $teamTwo */
/** @var int $teamThree */


class PizzaCombination{
    public int $id;
    /** @var Pizza[] */
    public $pizzas;
    public int $score;
    public int $lostIngred;
    public int $uniqueIngr;
    /** @var Ingredient[] */
    public $mergedIngred;
    public function __construct($pizzas)
    {
        $this->pizzas=$pizzas;
    }
}

//$pizzaCombo = [];

function calculateComboPizzaMerge($bestCombos, $pizzas){
    $newCombos=[];
    foreach ($bestCombos as $k => $combo) {
        $bestOne = null;
        foreach ($pizzas as $pizza2) {
            if (!in_array($pizza2->id, $combo->pizzas)) {
                $c = new PizzaCombination(array_merge($combo->pizzas, [$pizza2->id]));
                $totalIngr = count($combo->mergedIngred) + count($pizza2->ingredients);
                $mergedIngr = array_merge($combo->mergedIngred, $pizza2->ingredients);
                $c->lostIngred = $totalIngr - count($mergedIngr);
                $c->uniqueIngr = count($mergedIngr);
                $c->mergedIngred = $mergedIngr;
                if(!$bestOne || $bestOne->uniqueIngr<$c->uniqueIngr)
                    $bestOne = $c;
            }
        }
        $newCombos[]=$bestOne;
    }
    return $newCombos;
}

function calculateCombinations($comboLength)
{
//trovo le coppie da 2
    global $pizzas;
    foreach ($pizzas as $k => $pizza) {
        $bestOne = null;
        foreach ($pizzas as $k => $pizza2) {
            if ($pizza->id !== $pizza2->id) {
                $combo = new PizzaCombination([$pizza->id, $pizza2->id]);
                $totalIngr = count($pizza->ingredients) + count($pizza2->ingredients);
                $mergedIngr = array_merge($pizza->ingredients, $pizza2->ingredients);
                $combo->lostIngred = $totalIngr - count($mergedIngr);
                $combo->uniqueIngr = count($mergedIngr);
                $combo->mergedIngred = $mergedIngr;
                if(!$bestOne || $bestOne->uniqueIngr<$combo->uniqueIngr)
                    $bestOne = $combo;

            }
        }
        $pizzaCombo[] = $combo;
    }

    usort($pizzaCombo, function ($a, $b) {
        return $a->uniqueIngr < $b->uniqueIngr;
    });
    $bestCombos = $pizzaCombo;
    if($comboLength===2)
        return $bestCombos;
    Log::out('inizio giro 3');

    $bestCombos= calculateComboPizzaMerge($bestCombos, $pizzas);
    usort($bestCombos, function ($a, $b) {
        return $a->uniqueIngr < $b->uniqueIngr;
    });
    if($comboLength===3)
        return $bestCombos;
    Log::out('inizio giro 4');
    $bestCombos = calculateComboPizzaMerge($bestCombos, $pizzas);
    usort($bestCombos, function ($a, $b) {
        return $a->uniqueIngr < $b->uniqueIngr;
    });
    return $bestCombos;
}


$best4Combos=calculateCombinations(4, $pizzas);
$pizzasToDeliver = $teamFour + $teamThree* $teamTwo;

$content = "";
$teams =0;
$takenPizzaId=[];
$index=0;
while($teamFour + $teamThree+ $teamTwo >0){
    if(count($takenPizzaId)===500)
        return;
    if($teamFour>0){
        $best= $best4Combos[$index];
        $index++;
        if(!array_intersect($best->pizzas, $takenPizzaId)) {
            $content= $content . "4". " ";
            $takenPizzaId=array_merge($takenPizzaId, $best->pizzas);
            foreach ($best->pizzas as $pizzaId)
                $content = $content . $pizzaId . " ";
            $teamFour--;
            $teams++;
            $content = $content . "\n";
        }
    }
    else break;
}

//$content = $teams . "\n" . $content;
/*
function choseBestPizza( $len){
    global $pizzas;
    $combos = calculateCombinations($len);
    $best = $combos[0];
    foreach ($best->pizzas as $p){
        unset($pizzas[$p->id]);
    }
    return $best;
}

while(count($pizzas)>0){
    if(count($pizzas)<4)
        $teamFour=0;
    if(count($pizzas)<3)
        $teamThree=0;
    if(count($pizzas)<2)
        $teamTwo= 0;
    if($teamFour>0)
    {
        $content= $content . "4". " ";
        $combination = choseBestPizza(4);
        foreach($combination->pizzas as $p){
            $content = $content . $p->id . " ";
        }
        $content = $content . "\n";
        $teams++;
        $teamFour--;
    }elseif($teamThree>0){
        $content= $content . "3". " ";
        $combination = choseBestPizza(3);
        foreach($combination->pizzas as $p){
            $content = $content . $p->id . " ";
        }
        $content = $content . "\n";
        $teams++;
        $teamThree--;
    }elseif($teamTwo>0){
        $content= $content . "2". " ";
        $combination = choseBestPizza(2);
        foreach($combination->pizzas as $p){
            $content = $content . $p->id . " ";
        }
        $content = $content . "\n";
        $teams++;
        $teamTwo--;
    }else{
        break;
    }
}*/

$content = $teams . "\n" . $content;
Log::out('test');
file_put_contents('sarasol'.$fileName . '.txt', $content);
//\Utils\Autoupload::submission('b','test1.txt', $content);

Log::out('test');