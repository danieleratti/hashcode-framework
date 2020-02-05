<?php

namespace Utils;

class JSerializer
{
    private static function fileName($name)
    {
        return 'serialize/' . $name . '.j';
    }

    public static function set($name, $obj)
    {
        if (!is_dir('serialize'))
            mkdir('serialize');
        File::write(self::fileName($name), \json_encode($obj));
    }

    public static function get($name)
    {
        return \json_decode(file_get_contents(self::fileName($name)), true);
    }

    public static function clean($name)
    {
        unlink(self::fileName($name));
    }
}
