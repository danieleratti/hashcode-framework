<?php
use Utils\Log;

error_reporting(E_ALL);

require_once '../../bootstrap.php';

$fileName = 'b';

include 'reader-seb.php';

// Reader variables
global $fileManager, $companies, $developers,$managers, $office;

/**
 * @param Employee $employeStart
 * @return mixed|null
 */
function getBestEmployeBySkills($employeStart)
{
    global $employees;

    if (is_object($employeStart)) {
        $bestScore = 0;
        $bestEmployeeByTop = null;
        foreach ($employees as $employee) {
            /** @var Employee $employee */
            if ($employee->type == 'D' && empty($employee->coordinates)) {
                if($employee->company == $employeStart->company)
                    $localScore = $employee->bonus * $employeStart->bonus;
                else $localScore = 0;

                $skillsComuni = count(array_intersect($employee->skills, $employeStart->skills));
                $skillsDiverse = max(count($employee->skills), count($employeStart->skills)) - $skillsComuni;
                /*if ($skillsComuni == count($employeStart->skills) && count($employee->skills) == count($employeStart->skills)) {
                    //le skills di entrambi i dipendenti sono identifici score = 0
                    continue;
                } else */if ($bestScore == 0 || $bestScore < ($skillsComuni * $skillsDiverse + $localScore)) {
                    $bestScore = ($skillsComuni * $skillsDiverse) + $localScore;
                    $bestEmployeeByTop = $employee;
                }
            } else continue;
        }

        return ['employee' => $bestEmployeeByTop, 'localScore' => $bestScore];
    } else
        return ['localScore' => 0];
}


function getBestDeveloperHere($rowId, $columnId)
{
    global $employees, $office, $posizionato, $developers, $worstPopularCompany, $filename;


    if($office[$rowId][$columnId - 1] == '_' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
        // getWorstDeveloper()

        $developers2 = $developers;
        $keys = array_keys($developers2);
        array_multisort(
            array_column($developers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers2, $keys
        );
        $developers2 = array_combine($keys, $developers2);

        foreach($developers2 as $developer) {
            if($developer->company == $worstPopularCompany) {
                return $developer;
            }
        }
    }

    if(true || !in_array($filename, ['b', 'c', 'e'])) {
        //Solo un Manager a sinistra
        if ($office[$rowId][$columnId - 1] == 'M' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
            $developers2 = $developers;
            $keys = array_keys($developers2);
            array_multisort(
                array_column($developers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers2, $keys
            );
            $developers2 = array_combine($keys, $developers2);

            foreach ($developers2 as $developer) {
                if ($developer->company == $worstPopularCompany) {
                    return $developer;
                }
            }
        }

        //Solo un Manager a sopra
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == 'M' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
            $developers2 = $developers;
            $keys = array_keys($developers2);
            array_multisort(
                array_column($developers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers2, $keys
            );
            $developers2 = array_combine($keys, $developers2);

            foreach ($developers2 as $developer) {
                if ($developer->company == $worstPopularCompany) {
                    return $developer;
                }
            }
        }

        //Solo un Manager a destra
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == 'M' && $office[$rowId - 1][$columnId] == '#') {
            $developers2 = $developers;
            $keys = array_keys($developers2);
            array_multisort(
                array_column($developers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers2, $keys
            );
            $developers2 = array_combine($keys, $developers2);

            foreach ($developers2 as $developer) {
                if ($developer->company == $worstPopularCompany) {
                    return $developer;
                }
            }
        }

        //Solo un manager sotto
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == 'M') {
            $developers2 = $developers;
            $keys = array_keys($developers2);
            array_multisort(
                array_column($developers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers2, $keys
            );
            $developers2 = array_combine($keys, $developers2);

            foreach ($developers2 as $developer) {
                if ($developer->company == $worstPopularCompany) {
                    return $developer;
                }
            }
        }
    }

    if ($posizionato == 0) {

        //se sono nella situa:
        /**
         * ###
         * #D#
         * ###
         * trovo il developer piu merda di tutti ovvero quello con meno skills (forse con azienda meno popolare? tra tutti)
         */


        $bestDeveloper = null;
        foreach ($employees as $employee) {
            if ($employee->type = 'D' && empty($employee->coordinates)) {
                $bestDeveloper = $employee;
                break;
            }
        }

        if ($office[$rowId + 1][$columnId] == '_') {
            return getBestEmployeBySkills($bestDeveloper);
        }
        if ($office[$rowId - 1][$columnId] == '_') {
            return getBestEmployeBySkills($bestDeveloper);
        }
        if ($office[$rowId][$columnId -1 ] == '_') {
            return getBestEmployeBySkills($bestDeveloper);
        }
        if ($office[$rowId][$columnId  + 1 ] == '_') {
            return getBestEmployeBySkills($bestDeveloper);
        }

        return $bestDeveloper;
    } else {
        $topEmploye = $office[$rowId][$columnId - 1];
        $bestEmployeByTop = getBestEmployeBySkills($topEmploye);

        $rightEmploye = $office[$rowId + 1][$columnId];
        $bestEmployeByRight = getBestEmployeBySkills($rightEmploye);


        $bottomEmploye = $office[$rowId][$columnId + 1];
        $bestEmployeByBottom = getBestEmployeBySkills($bottomEmploye);

        $leftEmployee = $office[$rowId - 1][$columnId];
        $bestEmployeByLeft = getBestEmployeBySkills($leftEmployee);

        $bestScore = max([$bestEmployeByTop['localScore'], $bestEmployeByRight['localScore'], $bestEmployeByBottom['localScore'], $bestEmployeByLeft['localScore']]);

        if ($bestEmployeByTop['localScore'] && $bestEmployeByTop['localScore'] == $bestScore) return $bestEmployeByTop['employee'];
        if ($bestEmployeByRight['localScore'] && $bestEmployeByRight['localScore'] == $bestScore) return $bestEmployeByRight['employee'];
        if ($bestEmployeByBottom['localScore'] && $bestEmployeByBottom['localScore'] == $bestScore) return $bestEmployeByBottom['employee'];
        if ($bestEmployeByLeft['localScore'] && $bestEmployeByLeft['localScore'] == $bestScore) return $bestEmployeByLeft['employee'];
        else {
            foreach ($employees as $employee) {
                if ($employee->type == 'D' && empty($employee->coordinates)) {
                    return $employee;
                }
            }
        }
    }
}

function getBestManagerHere($rowId, $columnId)
{
    global $managers, $office, $worstPopularCompany, $filename;

    // nuovo controllo ->

    // Non capita quasi mai questa roba qui
    if($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
        // getWorstDeveloper()

        $managers2 = $managers;
        $keys = array_keys($managers2);
        array_multisort(
            array_column($managers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $managers2, $keys
        );
        $managers2 = array_combine($keys, $managers2);

        foreach($managers2 as $manager) {
            if($manager->company == $worstPopularCompany) {
                return $manager;
            }
        }
    }

    if(!in_array($filename, ['b', 'c', 'e'])) {
        //Solo un Manager a sinistra
        if ($office[$rowId][$columnId - 1] == 'M' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
            $managers2 = $managers;
            $keys = array_keys($managers2);
            array_multisort(
                array_column($managers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $managers2, $keys
            );
            $managers2 = array_combine($keys, $managers2);

            foreach($managers2 as $manager) {
                if($manager->company == $worstPopularCompany) {
                    return $manager;
                }
            }
        }

        //Solo un Manager a sopra
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == 'M' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == '#') {
            $managers2 = $managers;
            $keys = array_keys($managers2);
            array_multisort(
                array_column($managers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $managers2, $keys
            );
            $managers2 = array_combine($keys, $managers2);

            foreach($managers2 as $manager) {
                if($manager->company == $worstPopularCompany) {
                    return $manager;
                }
            }
        }

        //Solo un Manager a destra
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == 'M' && $office[$rowId - 1][$columnId] == '#') {
            $managers2 = $managers;
            $keys = array_keys($managers2);
            array_multisort(
                array_column($managers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $managers2, $keys
            );
            $managers2 = array_combine($keys, $managers2);

            foreach($managers2 as $manager) {
                if($manager->company == $worstPopularCompany) {
                    return $manager;
                }
            }
        }

        //Solo un manager sotto
        if ($office[$rowId][$columnId - 1] == '#' && $office[$rowId + 1][$columnId] == '#' && $office[$rowId][$columnId + 1] == '#' && $office[$rowId - 1][$columnId] == 'M') {
            $managers2 = $managers;
            $keys = array_keys($managers2);
            array_multisort(
                array_column($managers2, 'bonus'), SORT_ASC, SORT_NUMERIC, $managers2, $keys
            );
            $managers2 = array_combine($keys, $managers2);

            foreach($managers2 as $manager) {
                if($manager->company == $worstPopularCompany) {
                    return $manager;
                }
            }
        }
    }

    // codice vecchio ->

    $topEmploye = $office[$rowId - 1][$columnId];
    $rightEmploye = $office[$rowId][$columnId + 1];
    $bottomEmploye = $office[$rowId + 1][$columnId];
    $leftEmployee = $office[$rowId][$columnId - 1];

    $arrayCompanies = [
        is_object($topEmploye) ? $topEmploye->company : null,
        is_object($rightEmploye) ? $rightEmploye->company : null,
        is_object($bottomEmploye) ? $bottomEmploye->company : null,
        is_object($leftEmployee) ? $leftEmployee->company : null
    ];

    $companies = array_count_values($arrayCompanies);
    arsort($companies);
    $mostPopularCompany = array_slice(array_keys($companies), 0, 1, true)[0];

    $bestManager = null;
    foreach ($managers as $manager) {
        if (!empty($manager->coordinates)) continue;

        if ($mostPopularCompany) {
            if ($manager->company == $mostPopularCompany) {
                $bestManager = $manager;
                break;
            }
        } else {
            // SUPER MIGLIORAMENTO prendere in questo caso il peggior manager con l'azienda piu scrausa e il bonus minore di tutti
            // ora viene preso il primo porca troia
            $bestManager = getWorstManager();
            break;
        }
    }

    if(!$bestManager) {
        foreach($managers as $manager) {
            if(empty($manager->coordinates)) return $manager;
        }
    }

    return $bestManager;

}

function getWorstManager() {
    global $managers, $worstPopularCompany;

    $managers2 = $managers;
    $keys = array_keys($managers2);
    array_multisort(
        array_column($managers2, 'bonus'), SORT_DESC, SORT_NUMERIC, $managers2, $keys
    );
    $managers2 = array_combine($keys, $managers2);

    foreach($managers2 as $manager) {
        if(!empty($manager->coordinates)) continue;
        if($manager->company == $worstPopularCompany)
            return $manager;
    }

    foreach($managers2 as $manager) {
        if(!empty($manager->coordinates)) continue;
        return $manager;
    }

    return array_values($managers2)[0];
}

$positions = [];

function cmp($a, $b)
{
    if ($a->bonus == $b->bonus) {
        return 0;
    }
    return ($a->bonus > $b->bonus) ? -1 : 1;
}

function cmp3($a, $b)
{
    if ($a->bonus == $b->bonus) {
        return 0;
    }
    return ($a->bonus < $b->bonus) ? -1 : 1;
}

function cmp2($a, $b)
{
    if (count($a->skills) == count($b->skills)) {
        return 0;
    }
    return (count($a->skills) < count($b->skills)) ? -1 : 1;
}

$descCompanies = $companies;
array_multisort($descCompanies, SORT_DESC, array_keys($descCompanies));
$mostPopularCompany = array_keys($descCompanies)[0];

$ascCompanies = $companies;
array_multisort($ascCompanies, SORT_ASC, array_keys($descCompanies), 'bonus');
$worstPopularCompany = array_keys($ascCompanies)[0];

$keys = array_keys($developers);
array_multisort(array_column($developers, 'bonus'), SORT_ASC, SORT_NUMERIC, $developers, $keys);
$developers = array_combine($keys, $developers);

// Ordino i Managers per bonus desc
$keys = array_keys($managers);
array_multisort(
    array_column($managers, 'bonus'), SORT_DESC, SORT_NUMERIC, $managers, $keys
);

$managers = array_combine($keys, $managers);


$output = [];

$posizionato = 0;

global $height, $width, $employees;

for ($rowId = 0; $rowId < $height; $rowId++) {
    for ($columnId = 0; $columnId < $width; $columnId++) {
        //if ($filename == 'e' && ciclaPiuVeloce($rowId, $columnId)) continue;

        if ($office[$rowId][$columnId] == '#' || is_object($office[$rowId][$columnId])) continue;
        else if ($office[$rowId][$columnId] == '_') {
            Log::out("Posizionando Developer in [$rowId][$columnId]", 0);
            // Developer position
            $bestDeveloper = getBestDeveloperHere($rowId, $columnId);
            if ($bestDeveloper) {
                $posizionato++;
                $employees[$bestDeveloper->id]->coordinates = [$rowId, $columnId];
                $office[$rowId][$columnId] = $bestDeveloper;
            } else Log::out("Nessun Developer trovato disponibile " . count($employees), 0);

        } else if ($office[$rowId][$columnId] == 'M') {
            //Manager position
            Log::out("Posizionando Manager in [$rowId][$columnId]", 0);
            $bestManager = getBestManagerHere($rowId, $columnId);
            if ($bestManager) {
                $posizionato++;
                Log::out("Posizionato Manager ID = $bestManager->id", 0);
                $employees[$bestManager->id]->coordinates = [$rowId, $columnId];
                $managers[$bestManager->id]->coordinates = [$rowId, $columnId];
                $office[$rowId][$columnId] = $bestManager;
            } else  Log::out("Nessun Manager trovato disponibile " . count($managers), 0);
        }
    }
}

$output = [];

/** @var Employee $dipendente */
foreach ($employees as $dipendente) {
    if (!empty($dipendente->coordinates)) {
        $output[] = $dipendente->coordinates[1] . ' ' . $dipendente->coordinates[0];
    } else $output[] = 'X';
}


$fileManager->output(implode(PHP_EOL, $output));
