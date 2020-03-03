<?php

namespace Utils;

class Serializer
{
    private static function fileName($name, $method = 'serialize')
    {
        $ext = 's';
        switch ($method) {
            case 'serialize':
                $ext = 's';
                break;
            case 'json':
                $ext = 'json';
                break;
            case 'flat':
            default:
                $ext = 'txt';
                break;
        }
        return 'serialize/' . $name . '.' . $ext;
    }

    public static function set($name, $obj, $method = 'serialize')
    {
        if (!is_dir('serialize'))
            mkdir('serialize');
        switch ($method) {
            case 'serialize':
                $obj = \serialize($obj);
                break;
            case 'json':
                $obj = json_encode($obj);
                break;
            case 'flat':
            default:
                //$obj = $obj;
                break;
        }
        File::write(self::fileName($name, $method), $obj);
    }

    public static function get($name, $method = 'serialize')
    {
        $content = file_get_contents(self::fileName($name, $method));
        switch ($method) {
            case 'serialize':
                return \unserialize($content);
                break;
            case 'json':
                return json_decode($content, true);
                break;
            case 'flat':
            default:
                return $content;
                break;
        }
    }

    public static function clean($name, $method = 'serialize')
    {
        unlink(self::fileName($name, $method));
    }

    public static function getContext($_name, $_context)
    {
        $__newcontext = [];
        foreach ($_context as $_var) {
            $__newcontext[$_var] = self::get($_name . '/' . $_var);
            if (!$__newcontext[$_var]) {
                return false;
            }
        }
        foreach ($__newcontext as $_var => $_obj) {
            global $$_var;
            $$_var = $_obj;
        }
        return true;
    }

    public static function setContext($_name, $_context)
    {
        if (!is_dir('serialize'))
            mkdir('serialize');
        if (!is_dir('serialize/' . $_name))
            mkdir('serialize/' . $_name);
        foreach ($_context as $_var) {
            global $$_var;
            Log::out('Saving context for ' . $_var . '...', 1, 'gray');
            self::set($_name . '/' . $_var, $$_var);
        }
    }

    public static function cleanContext($name)
    {
        foreach ($files1 = scandir('serialize/' . $name) as $fname) {
            if ($fname != "." && $fname != "..") {
                list($fname) = explode(".s", $fname);
                self::clean($name . '/' . $fname);
            }
        }
    }
}
