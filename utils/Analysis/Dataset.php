<?php


namespace Utils\Analysis;


class Dataset
{
    /** @var string $name */
    public string $name;
    /** @var array $data */
    public array $data;
    /** @var string[] $properties */
    public array $properties;

    public function __construct(string $name, array $data, array $properties)
    {
        $this->name = $name;
        $this->data = $data;
        $this->properties = $properties;
    }
}
