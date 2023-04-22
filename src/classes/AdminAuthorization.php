<?php
trait AdminAuthorization
{
    protected function authorize(?Employee $user): void
    {
        if (!$user || !$user->admin) {
            throw new ForbiddenException("You don't have permission to access this.");
        }
    }
}
