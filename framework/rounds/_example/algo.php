<?php

use Src\HashcodeSolution;
use Src\Model;
use Src\RoundManager;

/** @var RoundManager $runner */
$runner = require_once '../../bootstrap.php';

class Street extends Model
{
    public $start, $end, $duration;
    public $name;
    public $usedByCars = [];

    public function __construct(int $start, int $end, string $name, int $duration)
    {
        $this->start = $start;
        $this->end = $end;
        $this->name = $name;
        $this->duration = $duration;
        parent::__construct($name);
    }
}

class Car extends Model
{
    public $streets = [];

    public function __construct($id, $streets)
    {
        $this->streets = $streets;
        parent::__construct($id);
    }
}

class Intersection extends Model
{
    public $streetsIn;
    public $streetsOut;
}

class ExampleHashcodeSolution extends HashcodeSolution
{

    public function run()
    {
        list($DURATION, $N_INTERSECTIONS, $N_STREETS, $N_CARS, $BONUS) = explode(' ', $this->inputNextLine());

        $inputStreets = $this->inputNextChunk($N_STREETS);
        $inputCars = $this->inputNextChunk($N_CARS);

        for ($i = 0; $i < $N_INTERSECTIONS; $i++)
            $intersection = new Intersection($i);

        foreach ($inputStreets as $key => $inputStreet) {
            list($start, $end, $name, $duration) = explode(" ", $inputStreet);
            $street = new Street((int)$start, (int)$end, $name, (int)$duration);
            Intersection::getOne($start)->streetsOut[] = $street;
            Intersection::getOne($end)->streetsIn[] = $street;
        }

        foreach ($inputCars as $key => $inputCar) {
            $carStreets = array_slice(explode(" ", $inputCar), 1);
            $car = new Car($key, $carStreets);
            foreach ($car as $street) {
                $street->usedBy[] = $key;
            }
        }
    }
}

$runner
    ->toRunInputs(['a', 'b', 'c', 'd', 'e'])//, 'b', 'c', 'd', 'e'
    ->run(ExampleHashcodeSolution::class);
