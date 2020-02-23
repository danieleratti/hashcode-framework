<?php


class Analyzer
{
    /** @var string|null $filename */
    private $filename = null;
    /** @var array */
    private $baseData = [];
    /** @var Dataset[] */
    private $data = [];

    public function __construct(string $filename, array $baseData)
    {
        $this->filename = $filename;
        $this->baseData = $baseData;
    }

    public function addDataset(string $name, array $data, array $properties)
    {
        $this->data[] = new Dataset($name, $data, $properties);
    }

    const PRINT_BOLD = 0x00001;
    const PRINT_ITALIC = 0x00010;
    const PRINT_UNDERLINED = 0x00100;
    const PRINT_GREEN = 0x01000;
    const PRINT_RED = 0x10000;

    public function print($string, $flags = 0)
    {
        if ($flags & self::PRINT_BOLD)
            $string = "<b>$string</b>";
        if ($flags & self::PRINT_ITALIC)
            $string = "<i>$string</i>";
        if ($flags & self::PRINT_UNDERLINED)
            $string = "<u>$string</u>";
        if ($flags & self::PRINT_GREEN)
            $string = "<span style='color: green'>$string</span>";
        if ($flags & self::PRINT_RED)
            $string = "<<span style='color: red'>$string</span>";
        echo $string;
    }

    public function println($string = "", $newLines = 1, $flags = 0)
    {
        $this->print($string, $flags);
        $this->print(str_repeat("<br/>", $newLines));
    }

    public function analyze()
    {
        ob_start();
        $this->println("FILE DI INPUT {$this->filename}", 1, self::PRINT_BOLD);

        // Base data
        foreach ($this->baseData as $name => $value) {
            $this->println("$name: $value");
        }
        $this->println();

        // Datasets
        foreach ($this->data as $dIdx => $dataset) {
            $this->println("SET {$dataset->name}", 1, self::PRINT_UNDERLINED);
            $this->println(count($dataset->data) . " elementi.", 2);
            if (!$dataset->data || count($dataset->data) === 0) {
                $this->println("Set inesistente o non inizializzato.", 2);
                continue;
            }
            foreach ($dataset->properties as $property) {
                try {
                    $reflection = new \ReflectionClass(get_class($dataset->data[0]));
                    [, , $type] = explode(' ', $reflection->getProperty($property)->getDocComment());
                    if (in_array($type, ['int', 'double'])) {
                        $type = 'number';
                    } elseif ($type == 'array' || strpos($type, '[]') !== false) {
                        $type = 'array';
                    }
                    $this->println("Analizzo {$property} [{$type}].");
                    // Analisi
                    $minValue = null;
                    $maxValue = null;
                    $sum = 0;
                    $occurrences = [];
                    foreach ($dataset->data as $data) {
                        $value = $type === 'number' ? $data->{$property} : count($data->{$property});
                        if ($minValue === null || $value < $minValue) {
                            $minValue = $value;
                        }
                        if ($maxValue === null || $value > $maxValue) {
                            $maxValue = $value;
                        }
                        $sum += $value;
                        $occurrences[(string)$value]++;
                    }
                    arsort($occurrences);
                    $this->println("Minimo: {$minValue}");
                    $this->println("Massimo: {$maxValue}");
                    //$this->println("Somma: {$sum}");
                    $this->println("Media: " . ($sum / count($dataset->data)));
                    $this->println("Mediana: " . (($maxValue + $minValue) / 2));
                    $this->println("Moda e tendenza:");
                    $i = 0;
                    foreach ($occurrences as $k => $v) {
                        $this->println("&nbsp;&nbsp;• $k ($v occorrenze)");
                        $i++;
                        if ($i > 10) break;
                    }
                    $this->println();
                } catch (Exception $e) {
                    $this->println("Errore: $e", 2);
                }
            }
            $this->println();
        }
        $output = ob_get_clean();
        file_put_contents('analysis/' . $this->filename . '.html', $output);
    }
}

class Dataset
{
    /** @var string $name */
    public $name;
    /** @var array $data */
    public $data;
    /** @var string[] $properties */
    public $properties;

    public function __construct(string $name, array $data, array $properties)
    {
        $this->name = $name;
        $this->data = $data;
        $this->properties = $properties;
    }
}
