<?php

abstract class CRUDPage extends BasePage
{

    protected function redirect(string $action, bool $success): void
    {
        $data = [
            'action' => $action,
            'success' => $success ? 1 : 0
        ];
        header('Location: list.php?' . http_build_query($data));
        exit;
    }
}
