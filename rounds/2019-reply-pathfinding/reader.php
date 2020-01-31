<?php

use Utils\DirUtils;
use Utils\FileManager;

require_once '../../bootstrap.php';

class Cell
{
    public $row;
    public $col;
    public $cost;
    public $char;
    public $utilizable;

    static $charCost = [
        '#' => -1,
        '~' => 800,
        '*' => 200,
        '+' => 150,
        'X' => 120,
        '_' => 100,
        'H' => 70,
        'T' => 50,
    ];

    public function __construct($row, $col, $char)
    {
        $this->row = $row;
        $this->col = $col;
        $this->char = $char;
        $this->cost = self::$charCost[$char];

        $this->utilizable = $char != '#';
    }

    public function __toString()
    {
        return $this->char;
    }
}

class Client
{
    public $id;
    public $row;
    public $col;
    public $revenue;

    public function __construct($id, $row, $col, $revenue)
    {
        $this->id = $id;
        $this->row = $row;
        $this->col = $col;
        $this->revenue = $revenue;
    }
}


class Map
{
    /** @var Cell[][] $cells */
    public $cells;
    public $rowCount;
    public $colCount;

    public $linkableClients;
    public $linkedClients;

    public $placedOffices = [];

    public function __construct($charArray, $clients)
    {
        foreach ($charArray as $row => $mapRow) {
            foreach (str_split($mapRow, 1) as $col => $mapCellChar) {
                $this->cells[$row][$col] = new Cell($row, $col, $mapCellChar);
            }
        }

        $this->rowCount = count($this->cells);
        $this->colCount = count($this->cells[0]);

        $this->linkableClients = $clients;

        /** @var Client $client */
        foreach ($clients as $client) {
            $this->cells[$client->row][$client->col]->utilizable = false;
        }
    }

    public function __toString()
    {
        $result = '';
        foreach ($this->cells as $row) {
            foreach ($row as $cell) {
                $result .= $cell;
            }
            $result .= "\n";
        }

        return $result;
    }

    public function placeOffice(Cell $cell, $clients)
    {
        $this->placedOffices[] = ['row' => $cell->row, 'col' => $cell->col];
        $this->cells[$cell->row][$cell->col]->utilizable = false;
        foreach ($clients as $client) {
            $this->linkClient($client);
        }
    }

    public function linkClient($client)
    {
        unset($this->linkableClients[$client->id]);
        $this->linkedClients[] = $client;
    }
}

class PathCell extends Cell
{
    public $path;
    public $pathCost;

    public function __construct($row, $col, $char, $path, $pathCost)
    {
        parent::__construct($row, $col, $char);
        $this->path = $path;
        $this->pathCost = $pathCost;
    }

    public function toString()
    {
        return $this->col . ' ' . $this->row . ' ' . $this->fixPath($this->path);
    }

    public function __toString()
    {
        return $this->toString();
    }

    function fixPath($path)
    {
        $result = '';
        for ($i = 0; $i < strlen($path); $i++) {
            switch ($path[$i]) {
                case 'D':
                    $result .= 'U';
                    break;
                case 'U':
                    $result .= 'D';
                    break;
                case 'L':
                    $result .= 'R';
                    break;
                case 'R':
                    $result .= 'L';
                    break;
            }
        }

        return $result;
    }
}

class PathMap
{
    /** @var Map $map */
    public $map;
    /** @var Client $client */
    public $client;

    /** @var PathCell[][] $cells */
    public $cells;
    /** @var PathCell[] $borderCells */
    public $borderCells;

    public $fileName;

    public function __construct(Map $map, Client $client, $fileName, $all = false)
    {
        $this->map = $map;
        $this->client = $client;
        $this->fileName = $fileName;

        $row = $client->row;
        $col = $client->col;
        /** @var Cell $clientCell */
        $clientCell = $map->cells[$row][$col];

        $dir = $this->getDir($all);
        $fhName = "$dir/" . $this->client->id . ".txt";

        if (file_exists($fhName)) {
            $this->fromFile(file_get_contents($fhName));
        } else {
            $firstCell = new PathCell($row, $col, $clientCell->char, '', 0);
            $this->cells[$row][$col] = $firstCell;
            $this->borderCells[] = $firstCell;
            $this->calculatePaths();
            $this->toFile($all);
        }
    }

    public function calculatePaths()
    {
        while (true) {
            //cerco quella con pathCost minore
            $minCost = PHP_INT_MAX;
            /** @var PathCell $minCell */
            $minCell = null;
            $minKey = null;

            foreach ($this->borderCells as $key => $borderCell) {
                $cost = $borderCell->pathCost + $borderCell->cost;
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
            $nearCells = [
                'U' => issetOrNull($this->map->cells[$minCell->row - 1][$minCell->col]),
                'D' => issetOrNull($this->map->cells[$minCell->row + 1][$minCell->col]),
                'L' => issetOrNull($this->map->cells[$minCell->row][$minCell->col - 1]),
                'R' => issetOrNull($this->map->cells[$minCell->row][$minCell->col + 1]),
            ];

            /** @var Cell $nearCell */
            foreach ($nearCells as $action => $nearCell) {
                // Se non l'ho trovata è perchè ho sforato dai limiti della mappa quindi continuo
                if (!$nearCell) continue;

                // Non considero mai quelle in cui non posso passare
                if ($nearCell->cost < 0) continue;

                // Se l'avevo già calcolata vuol dire che ho trovato un percorso migliore durante altre esecuzioni
                if (isset($this->cells[$nearCell->row][$nearCell->col])) continue;

                $validCell = new PathCell(
                    $nearCell->row,
                    $nearCell->col,
                    $nearCell->char,
                    $action . $minCell->path,
                    $minCell->pathCost + $minCell->cost
                );

                //valorizzo $this->cells per tutte queste celle (percordo e costo)
                $this->cells[$validCell->row][$validCell->col] = $validCell;
                //aggiungo tutto aggiunto $borderCells
                $this->borderCells[] = $validCell;
            }
        }
    }

    public function printCosts()
    {
        for ($r = 0; $r < $this->map->rowCount; $r++) {
            for ($c = 0; $c < $this->map->colCount; $c++) {
                $value = 'X';
                if ($this->cells[$r][$c])
                    $value = $this->cells[$r][$c]->pathCost;
                echo str_pad($value, 6, ' ');
            }
            echo "\n";
        }
    }

    public function toFile($all)
    {
        $dir = $this->getDir($all);
        DirUtils::makeDirOrCreate($dir);
        $fh = fopen("$dir/" . $this->client->id . ".txt", "w");

        foreach ($this->cells as $row) {
            /** @var PathCell $cell */
            foreach ($row as $cell) {
                /*
                if ($cell->pathCost >= $this->client->revenue)
                    continue;
                */
                $row = $cell->row;
                $col = $cell->col;
                $char = $cell->char;
                $path = $cell->path;
                $pathCost = $cell->pathCost;
                fwrite($fh, "$row $col $char $pathCost $path\n");
            }
        }
        fclose($fh);
    }

    public function fromFile($file)
    {
        $fileArray = explode("\n", $file);
        foreach ($fileArray as $fileRow) {
            if (!$fileRow)
                continue;
            list($row, $col, $char, $pathCost, $path) = explode(" ", $fileRow);
            $this->cells[$row][$col] = new PathCell($row, $col, $char, $path, $pathCost);
        }
    }

    public function getDir($all)
    {
        if ($all)
            return "cache/heavy/" . $this->fileName;
        return "cache/light/" . $this->fileName;
    }

    /** @return PathCell */
    public function getCell($row, $col)
    {
        if (!isset($this->cells[$row][$col]))
            return null;
        return $this->cells[$row][$col];
    }
}

$fileManager = new FileManager($fileName);

$content = str_replace("\r", "", $fileManager->get());
$content = explode("\n", $content);

list($colCount, $rowCount, $clientsCount, $maxOfficesCount) = explode(' ', $content[0]);

$clientsFileList = array_slice($content, 1, $clientsCount);
$mapRowsFile = array_slice($content, 1 + $clientsCount, $rowCount);

$clients = [];
foreach ($clientsFileList as $id => $clientRow) {
    list ($col, $row, $revenue) = explode(' ', $clientRow);
    $clients[$id] = new Client($id, $row, $col, $revenue);
}

$map = new Map($mapRowsFile, $clients);

$caches = [];
foreach ($clients as $key => $client) {
    $n = $key + 1;
    echo "Cache $n/$clientsCount caricata\n";
    $cache = new PathMap($map, $client, $fileManager->inputName);
    $caches[$client->id] = $cache;
}
