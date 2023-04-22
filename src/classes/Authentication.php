<?php
trait Authentication
{
    protected function authenticate(?Employee $user): void
    {
        if (!$user) {
            Utils::redirect_to_page('/login.php');
        }
    }
}
