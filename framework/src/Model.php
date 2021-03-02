<?php

namespace Src;

abstract class Model
{
    public $id;

    private static $array = [];

    public function __construct($id)
    {
        $this->id = $id;
        $class = get_class($this);
        if (!isset(self::$array[$class]))
            self::$array[$class] = [];
        self::$array[$class][$id] = $this;
    }

    /**
     * @param $id
     * @return Model[]
     */
    public static function getAll()
    {
        $class = get_called_class();
        return self::$array[$class];
    }

    /**
     * @param $id
     * @return Model
     */
    public static function getOne($id)
    {
        return self::getAll()[$id];
    }

    /**
     * @param $ids
     * @return Model[]
     */
    public static function getSome($ids)
    {
        $all = self::getAll();
        return array_map(function ($id) use ($all) {
            return $all[$id];
        }, $ids);
    }

    /**
     * @param $ids
     */
    public static function unsetSome($ids)
    {
        foreach ($ids as $id) {
            self::unsetOne($id);
        }
    }

    /**
     * @param $id
     */
    public static function unsetOne($id)
    {
        $class = get_called_class();
        unset(self::$array[$class][$id]);
    }

    /**
     * @param Model[] $value
     */
    public static function overwriteList($value)
    {
        $class = get_called_class();
        self::$array[$class] = $value;
    }

    public static function resetRound()
    {
        self::$array = [];
    }
}
