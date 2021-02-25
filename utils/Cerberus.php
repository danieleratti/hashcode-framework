<?php

/**
 * Cerberus standard:
 * the php script should always print only one line saying at what point is it, and the score
 * the php script should write a file named <scriptName>_<fileInputName>_<SCORE>.txt ( using $fileManager->output($output, $SCORE)); )
 */

namespace Utils;

class Cerberus
{
    public static $db;

    /*
     * run a Cerberus client asking for params $params
     * ['fileName' => 'a' (default value), 'var1' => 1]
     */
    public static function runClient($params)
    {
        global $CERBERUS_PARAMS;

        $argv = $_SERVER['argv'];

        foreach ($params as $param => $value) {
            global $$param;
            $$param = $value;
        }

        if ($argv[1] == 'cerberus') {
            $input = json_decode($argv[2], true);
            if (!$input)
                die('Wrong input ' . $argv[2]);
            else if ($input['action'] == 'info') {
                die(json_encode(array_keys($params)));
            } else if ($input['action'] == 'run') {
                foreach ($input['params'] as $param => $value) {
                    if (isset($params[$param])) {
                        global $$param;
                        $$param = $value;
                        $params[$param] = $value;
                    }
                }
            } else {
                die('Unknown input action');
            }
        }

        $CERBERUS_PARAMS = json_encode($params);
    }

    public static function runServer()
    {
        Log::$dates = false;
        self::interact();
    }

    public static function interact()
    {
        while (true) {
            self::readDb();
            echo chr(27) . chr(91) . 'H' . chr(27) . chr(91) . 'J';
            Log::out("\n" . 'Welcome to Cerberus', 0, 'white', 'red');

            Log::out("\n" . 'Commands', 1);
            Log::out('1. (l)aunch <scriptname without extension .php> (eg. launch sol-topo-1) [no scriptname = last scriptname]', 2);
            Log::out('2. (s)top <ID of a running script(s)> (eg. stop 123) (eg. stop 123,345,567) (eg. stop all)', 2);
            Log::out('3. (c)lear <ID of a finished script(s) or "all"> (eg. clear 123) (eg. clear 123,345,567) (eg. clear all)', 2);
            Log::out('4. (r)eset (stop all + clear all)', 2);
            Log::out('5. ENTER (refresh the list)', 2);

            $runningScripts = 0;
            foreach (self::$db['scripts'] as $id => $script) {
                if ($script['status'] == 'running') {
                    $lastline = shell_exec('tail -n 1 cerberus/' . $script['log']);
                    $script['lastline'] = $lastline;
                    if ($script['status'] == 'running' && !posix_getpgid($script['pid'])) {
                        $script['status'] = 'finished';
                    }
                    self::$db['scripts'][$id] = $script;
                }
            }
            self::writeDb();

            $scripts = self::$db['scripts'];
            ArrayUtils::array_keysort($scripts, 'status', SORT_DESC);
            Log::out("\n" . count($scripts) . ' scripts', 1);

            foreach ($scripts as $script) {
                Log::out($script['id'] . ') ' . $script['script'] . ' (' . json_encode($script['params']) . ') => ' . trim($script['lastline']), 1, $script['status'] == 'running' ? 'light_purple' : 'yellow');
            }


            Log::out("\n");

            $cmd = readline('Command? ');
            $cmd = explode(" ", $cmd);
            switch ($cmd[0]) {
                case 'l':
                case 'launch':
                    self::launch($cmd[1] ?: self::$db['lastLaunchScript']);
                    break;
                case 's':
                case 'stop':
                    self::stop($cmd[1]);
                    break;
                case 'c':
                case 'clear':
                    self::clear($cmd[1]);
                    break;
                case 'r':
                case 'reset':
                    self::reset();
                    break;
            }
        }
    }

    public static function launch($scriptName)
    {
        self::readDb();
        self::$db['lastLaunchScript'] = $scriptName;
        self::writeDb();

        $cmd = "php " . $scriptName . ".php cerberus '" . json_encode(["action" => "info"]) . "'";
        $ret = shell_exec($cmd);
        $params = json_decode($ret, true);
        if (!$params) {
            Log::out('Can\'t find ' . $scriptName . '.php!', 0, 'red');
            sleep(1);
            return;
        }

        Log::out('Launching ' . $scriptName . '...');

        $lvl = 0;
        $possibilities = [];

        foreach ($params as $param) {
            $value = readline('Param ' . $param . '? ');

            foreach (explode(",", $value) as $v) {
                if($lvl == 0)
                    $possibilities[$lvl][] = [$param => $v];
                else {
                    foreach($possibilities[$lvl-1] as $oldP) {
                        $oldP[$param] = $v;
                        $possibilities[$lvl][] = $oldP;
                    }
                }
            }
            $lvl++;
        }
        foreach ($possibilities[$lvl-1] as $possibility) {
            self::runScript($scriptName, $possibility);
        }
        usleep(500 * 1000);
    }

    public static function runScript($script, $params)
    {
        Log::out('Running ' . $script . ' with ' . json_encode($params), 1, 'green');
        $id = ++self::$db['lastIdx'];
        $cmd = "nohup php " . $script . ".php cerberus '" . json_encode(["action" => "run", "params" => $params]) . "' " . (PHP_OS_FAMILY === "Linux" ? ">" : "&>") . " cerberus/" . $id . ".log & echo $!";
        $pid = shell_exec($cmd);
        self::$db['scripts'][$id] = [
            'id' => $id,
            'script' => $script,
            'params' => $params,
            'log' => $id . '.log',
            'pid' => (int)trim($pid),
            'status' => 'running'
        ];
        Log::out('Run. PID=' . $pid, 1, 'green');
        self::writeDb();
    }

    public static function readDb()
    {
        DirUtils::makeDirOrCreate('cerberus');
        $db = json_decode(file_get_contents('cerberus/db.json'), true);
        if (!$db)
            $db = ['scripts' => []];
        self::$db = $db;
    }

    public static function writeDb()
    {
        File::write('cerberus/db.json', json_encode(self::$db));
    }

    public static function stop($ids)
    {
        $ids = explode(',', $ids);
        self::readDb();
        foreach (self::$db['scripts'] as $id => $script) {
            if ($script['status'] == 'running' && ($ids[0] == 'all' || in_array($script['id'], $ids))) {
                shell_exec('kill ' . $script['pid']);
                unlink('ceberus/' . $id . '.log');
                unset(self::$db['scripts'][$id]);
            }
        }
        self::writeDb();
    }

    public static function clear($ids)
    {
        $ids = explode(',', $ids);
        self::readDb();
        foreach (self::$db['scripts'] as $id => $script) {
            if ($script['status'] == 'finished' && ($ids[0] == 'all' || in_array($script['id'], $ids))) {
                unlink('ceberus/' . $id . '.log');
                unset(self::$db['scripts'][$id]);
            }
        }
        self::writeDb();
    }

    public static function reset()
    {
        self::stop('all');
        self::clear('all');
    }
}
