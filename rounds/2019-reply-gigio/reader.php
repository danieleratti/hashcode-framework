<?php

use Utils\DirUtils;
use Utils\FileManager;

require_once '../../bootstrap.php';

$charCost = [
    '#' => -1,
    '~' => 800,
    '*' => 200,
    '+' => 150,
    'X' => 120,
    '_' => 100,
    'H' => 70,
    'T' => 50,
];

class GClient
{
    public $id;
    public $row;
    public $col;
    public $revenue;

    public function __construct($id, $clientRow)
    {
        list ($col, $row, $revenue) = explode(' ', $clientRow);
        $this->id = $id;
        $this->row = $row;
        $this->col = $col;
        $this->revenue = $revenue;
    }
}

class GCell
{
    public $row;
    public $col;
    public $char;
    public $cost;
    public $buildable = true;
    public $clientsPaths;
    public $clientsPathsCosts;

    public function __construct($row, $col, $char)
    {
        global $charCost;
        $this->row = $row;
        $this->col = $col;
        $this->char = $char;
        $this->cost = $charCost[$char];
        $this->clientsPathsCosts = [];
        $this->clientsPaths = [];
        if ($char == '#')
            $this->buildable = false;
    }

    public function getRevenuesSum()
    {
        global $clients;

        $allRevenues = 0;
        $positiveRevenues = 0;
        $allClients = [];
        $positiveClients = [];

        if ($this->buildable) {
            foreach ($clients as $id => $client) {
                if (!isset($this->clientsPathsCosts[$id]))
                    continue;

                $revenue = $client->revenue - $this->clientsPathsCosts[$id];

                if ($revenue > 0) {
                    $positiveRevenues += $revenue;
                    $positiveClients[] = $id;
                }

                $allRevenues += $revenue;
                $allClients[] = $id;
            }
        }

        return [
            'allRevenues' => $allRevenues,
            'allClients' => $allClients,
            'positiveRevenues' => $positiveRevenues,
            'positiveClients' => $positiveClients,
        ];
    }
}

class GMap
{
    /** @var GCell[][] $cells */
    public $cells;
    /** @var GCell[] $borderCells */
    private $borderCells;

    private $rowCount;
    private $colCount;
    private $fileManager;
    private $clients;
    private $dir;

    public function __construct(FileManager $fileManager, array $mapRows, array $clients)
    {
        $this->fileManager = $fileManager;
        $this->clients = $clients;
        $this->rowCount = count($mapRows);
        $this->colCount = strlen($mapRows[0]);

        foreach ($mapRows as $rowId => $mapRow) {
            foreach (str_split($mapRow, 1) as $colId => $charCell) {
                $this->cells[$rowId][$colId] = new GCell($rowId, $colId, $charCell);
            }
        }

        foreach ($clients as $client) {
            $this->cells[$client->row][$client->col]->buildable = false;
        }

        $this->dir = DirUtils::getScriptDir() . '/cache/' . $fileManager->getInputName();

        $clientsCount = count($clients);
        /**
         * @var integer $clientId
         * @var GClient $client
         */
        foreach ($clients as $clientId => $client) {
            $costsFile = $this->dir . "/${clientId}_costs.txt";
            $pathsFile = $this->dir . "/${clientId}_paths.txt";

            if (file_exists($costsFile) && file_exists($pathsFile)) {
                $this->costsFromFile($clientId, $costsFile);
            } else {
                /** @var GCell $firstCell */
                $firstCell = $this->cells[$client->row][$client->col];
                $firstCell->clientsPathsCosts[$clientId] = 0;
                $firstCell->clientsPaths[$clientId] = '';
                $this->borderCells = [$firstCell];
                $this->calculatePaths($clientId);
                $this->toFile($clientId, $costsFile, $pathsFile);
            }

            echo "Cache " . ($clientId + 1) . "/$clientsCount caricata\n";
            unset($this->borderCells);
        }
    }

    private function costsFromFile($clientId, $costsFile)
    {
        $cacheRows = explode("\n", file_get_contents($costsFile));
        foreach ($cacheRows as $rowId => $cacheRow) {
            $pathsCosts = explode(",", $cacheRow);
            foreach ($pathsCosts as $colId => $pathCost) {
                $cell = $this->cells[$rowId][$colId];
                if ($pathCost == '')
                    continue;
                $cell->clientsPathsCosts[$clientId] = (int)$pathCost;
            }
        }
    }

    private function calculatePaths($clientId)
    {
        while (true) {
            //cerco quella con pathCost minore
            $minCost = PHP_INT_MAX;
            $minCell = null;
            $minKey = null;

            foreach ($this->borderCells as $key => $borderCell) {
                $cost = $borderCell->clientsPathsCosts[$clientId] + $borderCell->cost;
                if ($cost < $minCost) {
                    $minCost = $cost;
                    $minCell = $borderCell;
                    $minKey = $key;
                }
            }

            // Se non trovo niente ho finito
            if (!$minCell)
                break;

            //la tolgo dall'array di $borderCells
            unset($this->borderCells[$minKey]);

            //estraggo tutte le vicine la cui poszione non è ancora salvata in $this->cells
            $nearCells = $this->getNearCells($minCell);

            foreach ($nearCells as $action => $nearCell) {
                // Non considero mai quelle in cui non posso passare
                if ($nearCell->cost < 0) continue;

                // Se l'avevo già calcolata vuol dire che ho trovato un percorso migliore durante altre esecuzioni
                if (isset($nearCell->clientsPathsCosts[$clientId])) continue;

                $nearCell->clientsPathsCosts[$clientId] = $minCell->clientsPathsCosts[$clientId] + $minCell->cost;
                $nearCell->clientsPaths[$clientId] = $action . $minCell->clientsPaths[$clientId];

                //aggiungo quelle nuove alle $borderCells
                $this->borderCells[] = $nearCell;
            }
        }
    }

    /**
     * @param GCell $cell
     * @return GCell[]
     */
    private function getNearCells(GCell $cell)
    {
        $nearCells = [];
        $row = $cell->row;
        $col = $cell->col;
        $downRow = $row - 1;
        $upRow = $cell->row + 1;
        $rightCol = $cell->col - 1;
        $leftCol = $cell->col + 1;

        if ($downRow >= 0)
            $nearCells['D'] = $this->cells[$downRow][$col];
        if ($upRow < $this->rowCount)
            $nearCells['U'] = $this->cells[$upRow][$col];
        if ($rightCol >= 0)
            $nearCells['R'] = $this->cells[$row][$rightCol];
        if ($leftCol < $this->colCount)
            $nearCells['L'] = $this->cells[$row][$leftCol];

        return $nearCells;
    }

    private function toFile($clientId, $costsFileName, $pathsFileName)
    {
        DirUtils::makeDirOrCreate(dirname($costsFileName));
        DirUtils::makeDirOrCreate(dirname($pathsFileName));

        $costs = [];
        $paths = [];
        foreach ($this->cells as $cells) {
            $costsRow = [];
            $pathsRow = [];
            foreach ($cells as $cell) {
                $costsRow[] = issetOrVal($cell->clientsPathsCosts[$clientId], '');
                $pathsRow[] = issetOrVal($cell->clientsPaths[$clientId], '');
            }
            $costs[] = implode(',', $costsRow);
            $paths[] = implode(',', $pathsRow);
        }

        $costsFile = fopen($costsFileName, 'w');
        fwrite($costsFile, implode("\n", $costs));
        fclose($costsFile);

        $pathsFile = fopen($pathsFileName, 'w');
        fwrite($pathsFile, implode("\n", $paths));
        fclose($pathsFile);
    }

    public function outputSolution($solution)
    {
        $solutionByClient = [];
        foreach ($solution as $replyOffice) {
            foreach ($replyOffice['clients'] as $clientId) {
                $solutionByClient[$clientId][] = [
                    'row' => $replyOffice['row'],
                    'col' => $replyOffice['col'],
                ];
            }
        }

        $output = '';
        $score = 0;
        $bonus = collect($this->clients)->sum('revenue');
        $totalClients = count($this->clients);
        $missingClients = $totalClients - count($solutionByClient);

        if (!$missingClients)
            $score += $bonus;

        foreach ($solutionByClient as $clientId => $offices) {
            $pathsFile = $this->dir . "/${clientId}_paths.txt";
            $cacheRows = explode("\n", file_get_contents($pathsFile));
            foreach ($offices as $office) {
                $row = $office['row'];
                $col = $office['col'];
                $cacheRow = $cacheRows[$row];
                $path = explode(',', $cacheRow)[$col];
                $output .= "$col $row $path\n";
                $score += $this->clients[$clientId]->revenue - $this->cells[$row][$col]->clientsPathsCosts[$clientId];
            }
        }

        echo "\n\n";
        if ($missingClients) {
            echo "Senza il bonus ($bonus)\n";
            echo "clienti mancanti $missingClients su $totalClients\n";
        } else {
            echo "Hai guadagnato il bonus! ($bonus)\n";
        }
        echo "SCORE: $score";

        $this->fileManager->output($output);
    }

    public function getPathCost($row, $col, $clientId)
    {
        $costs = $this->cells[$row][$col]->clientsPathsCosts;
        if(!isset($costs[$clientId]))
            return null;
        return $costs[$clientId];
    }
}

$fileManager = new FileManager($fileName);

$content = str_replace("\r", "", $fileManager->get());
$content = explode("\n", $content);

list($colCount, $rowCount, $clientsCount, $maxOfficesCount) = explode(' ', $content[0]);

$clientsFileList = array_slice($content, 1, $clientsCount);
$mapRowsFile = array_slice($content, 1 + $clientsCount, $rowCount);

/** @var GClient[] $clients */
$clients = [];
foreach ($clientsFileList as $id => $clientRow) {
    $clients[$id] = new GClient($id, $clientRow);
}

$map = new GMap($fileManager, $mapRowsFile, $clients);

/*
Formato suluzioni accettato da mappa:
[
    [
        'row' => x,
        'col' => x,
        'clients' => [x,x,x]
    ]
]
 */
