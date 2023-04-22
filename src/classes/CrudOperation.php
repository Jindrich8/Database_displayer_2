<?php
class CrudOperation
{
    public ?Action $action;
    public ?Model $model;
    public ?ErrorCode $errorCode;
    public ?int $id;

    public function __construct(?Action $action, ?Model $model, ?int $id, ?ErrorCode $errorCode)
    {
        $this->action = $action;
        $this->model = $model;
        $this->errorCode = $errorCode;
        $this->id = $id;
    }
}
