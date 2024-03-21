<?php

namespace Utils\Analysis;

use Utils\Chart;
use Utils\Collection;

class Analyzer
{
    private ?string $filename;
    private array $baseData;
    private array $data;

    public function __construct(string $filename, array $baseData)
    {
        $this->filename = $filename;
        $this->baseData = $baseData;
    }

    /**
     * @param string $name
     * @param array|Collection $data
     * @param array $properties
     */
    public function addDataset(string $name, Collection|array $data, array $properties): void
    {
        if ($data instanceof Collection)
            $data = $data->toArray();
        $this->data[] = new Dataset($name, $data, $properties);
    }

    public function analyze(): void
    {
        $chart = new Chart('stats');
        ob_start();
        $this->initPage();
        $this->println("FILE DI INPUT {$this->filename}", 1, self::PRINT_BOLD | self::PRINT_TITLE);

        // Base data
        foreach ($this->baseData as $name => $value) {
            $this->println("$name: $value");
        }
        $this->println();
        $this->printDivider();

        // Datasets
        $elaborator = new Elaborator($this->data);
        $datasetsResults = $elaborator->elaborate();
        foreach ($datasetsResults['datasets'] as $datasetName => $dataset) {
            $this->println("SET {$datasetName}", 1, self::PRINT_UNDERLINED | self::PRINT_TITLE);
            $this->println($dataset['count'] . " elementi.", 2);
            if ($dataset['error']) {
                $this->println($dataset['error'], 2, self::PRINT_RED);
                continue;
            }
            foreach ($dataset['properties'] as $propertyName => $property) {
                $this->println("<br>✱ Analizzo <b>{$propertyName}</b> [{$property['type']}].");
                if ($property['error']) {
                    $this->println($property['error'], 2, self::PRINT_RED);
                    continue;
                }
                if ($property['minimum'] == $property['maximum']) {
                    $this->println('Questo parametro sembra trascurabile.', 1, self::PRINT_BLUE);
                }
                if (in_array($property['type'], ['array', 'collection']) && $property['minimum'] == 0) {
                    $this->println('Sembrano esserci liste vuote.', 1, self::PRINT_BLUE);
                }
                $propertyArray = [];
                $this->println("Minimo: {$property['minimum']}");
                $this->println("Massimo: {$property['maximum']}");
                $this->println("Somma: {$property['sum']}");
                $this->println("Media: {$property['average']}");
                $this->println("Mediana: {$property['median']}");
                $this->println("Moda e tendenza:");
                $i = 0;
                if ($property['minimum'] != $property['maximum']) {
                    foreach ($property['occurrences'] as $k => $v) {
                        $this->println("&nbsp;&nbsp;• $k ($v occorrenze)");
                        $i++;
                        if ($i > 10) break;
                    }
                    $chart = new Chart("{$this->filename}-$propertyName");
                    $chart->plotPoints(collect($property['occurrences'])->map(fn($v, $k) => [$k, $v]));
                }

            }
            $divName = $datasetName;
            $this->println("<div id={$divName}></div>");
            $chartBoxPlotHTML = $chart->getBoxPlotHtml($elaborator->boxPlotTraces[$datasetName], $divName);
            $this->println($chartBoxPlotHTML);
            $this->printDivider();

        }
        $this->print("</body>");
        $output = ob_get_clean();
        if(!is_dir('analysis'))
            mkdir('analysis');
        file_put_contents('analysis/' . $this->filename . '.html', $output);
    }

    /* PRINT FUNCTIONS */

    const PRINT_BOLD = 0x00000001;
    const PRINT_ITALIC = 0x00000010;
    const PRINT_UNDERLINED = 0x00000100;
    const PRINT_GREEN = 0x00001000;
    const PRINT_RED = 0x00010000;
    const PRINT_BLUE = 0x00100000;
    const PRINT_TITLE = 0x01000000;

    private function initPage(): void
    {
        echo "<style type='text/css'>body {font-family: sans-serif;}</style>";
        echo "<head>
                <script src='https://cdn.plot.ly/plotly-latest.min.js'></script>
            </head><body>";

    }

    public function print($string, $flags = 0): void
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
            $string = "<span style='color: red'>$string</span>";
        if ($flags & self::PRINT_BLUE)
            $string = "<span style='color: blue'>$string</span>";
        if ($flags & self::PRINT_TITLE)
            $string = "<span style='font-size: 18px;'>$string</span>";
        echo $string;
    }

    public function println($string = "", $newLines = 1, $flags = 0): void
    {
        $this->print($string, $flags);
        $this->print(str_repeat("<br/>", $newLines));
    }

    public function printDivider(): void
    {
        $this->print("<hr/>");
    }
}
