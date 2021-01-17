<?php


namespace Utils\Analysis;


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
