<?php
trait Authentication
{
    protected function authenticate(?Employee $user): void
    {
        if (!$user) {
            header("Location: /login.php");
            exit;
        }
    }
}
