<?php

namespace Utils;

class Serializer
{
    private static function fileName($name)
    {
        return 'serialize/' . $name . '.s';
    }

    public static function set($name, $obj)
    {
        if (!is_dir('serialize'))
            mkdir('serialize');
        File::write(self::fileName($name), \serialize($obj));
    }

    public static function get($name)
    {
        return \unserialize(file_get_contents(self::fileName($name)));
    }

    public static function clean($name)
    {
        unlink(self::fileName($name));
    }
}
