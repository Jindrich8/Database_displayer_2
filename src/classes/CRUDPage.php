<?php

abstract class CRUDPage extends BaseLoggedInPage
{

    protected function redirect(CrudAction $action, bool $success, string $message): void
    {
        $data = [
            'action' => $action->value,
            'success' => $success ? 1 : 0,
            'message' => $message,
        ];
        header('Location: list.php?' . http_build_query($data));
        exit;
    }
}
