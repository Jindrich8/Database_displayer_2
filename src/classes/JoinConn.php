<?php
class JoinConn
{
    public string $join;
    public ?string $joinAlias;
    /**
     * @param string[] $columns
     */
    public array $columns;
    public array $rawColumns;
    public string $on;

    /**
     * @param string[] $columns
     */
    public function __construct(string $join, string $joinAlias, string $on, ?array $columns = [],?array $rawColumns = [])
    {
        $this->join = $join;
        $this->joinAlias = $joinAlias;
        $this->columns = $columns;
        $this->on = $on;
        $this->rawColumns = $rawColumns;
    }
}
