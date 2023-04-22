<?php
class NameWAlias
{
    public string $name;
    public string $alias;

    public function __construct(string $name, string $alias)
    {
        $this->name = $name;
        $this->alias = $alias;
    }
}
