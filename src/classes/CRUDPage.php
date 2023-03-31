<?php

abstract class CRUDPage extends BasePage
{

    protected function redirect(CrudAction $action, bool $success): void
    {
        $data = [
            'action' => $action->value,
            'success' => $success ? 1 : 0
        ];
        header('Location: list.php?' . http_build_query($data));
        exit;
    }
}
