<?php


namespace Utils\Analysis;


use Exception;
use ReflectionClass;

class Elaborator
{
    public array $data;
    public array $boxPlotTraces;

    public function __construct($data)
    {
        $this->data = $data;
        $this->boxPlotTraces = [];

    }

    public function elaborate(): array
    {
        $boxPlotTraces = [];
        $results = [
            'datasets' => [],
        ];

        foreach ($this->data as $dIdx => $dataset) {
            $set = [
                'count' => $dataset->data,
                'properties' => [],
            ];

            if (!$dataset->data || count($dataset->data) === 0) {
                $set['error'] = 'Set inesistente o non inizializzato.';
                continue;
            }

            foreach ($dataset->properties as $property) {
                $item = [];

                try {
                    reset($dataset->data);

                    // Riconoscimento tipo
                    $reflection = new ReflectionClass(get_class($dataset->data[key($dataset->data)]));
                    [, , $type] = explode(' ', $reflection->getProperty($property)->getDocComment());
                    if (in_array($type, ['bool', 'int', 'double'])) {
                        $type = 'number';
                    } elseif ($type == 'array' || str_contains($type, '[]')) {
                        $type = 'array';
                    } elseif (str_contains($type, 'Collection')) {
                        $type = 'collection';
                    }
                    $item['type'] = $type;

                    // Analisi
                    $minValue = null;
                    $maxValue = null;
                    $sum = 0;
                    $occurrences = [];
                    $orderedValues = [];
                    $propertyArray = [];
                    foreach ($dataset->data as $data) {
                        switch ($type) {
                            case 'number':
                                $value = $data->{$property};
                                break;
                            case 'array':
                                $value = count($data->{$property});
                                break;
                            case 'collection':
                                $value = $data->{$property}->count();
                                break;
                            default:
                                $set['error'] = 'Tipo non atteso.';
                                continue 2;
                        }
                        $propertyArray[] = $value;
                        if ($minValue === null || $value < $minValue) {
                            $minValue = $value;
                        }
                        if ($maxValue === null || $value > $maxValue) {
                            $maxValue = $value;
                        }
                        $sum += $value;
                        $occurrences[(string)$value]++;
                        $orderedValues[] = $value;
                    }
                    $item['minimum'] = $minValue;
                    $item['maximum'] = $maxValue;
                    sort($orderedValues);
                    arsort($occurrences);
                    $n = count($dataset->data);
                    $item['average'] = $sum / $n;
                    $middleValueIndex = floor(($n - 1) / 2);
                    $item['median'] = $n % 2 ? $orderedValues[$middleValueIndex] : ($orderedValues[$middleValueIndex] + $orderedValues[$middleValueIndex + 1]) / 2;
                    foreach ($occurrences as $k => $v) {
                        $item['occurrences'][$k] = $v;
                    }
                    $boxPlotTraces[$property] = $propertyArray;
                } catch (Exception $e) {
                    $set['error'] = "$e";
                }

                $set['properties'][$property] = $item;
            }

            $this->boxPlotTraces[$dataset->name]=$boxPlotTraces;
            $results['datasets'][$dataset->name] = $set;
        }

        return $results;
    }

}
