<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class ChangePasswordActionPage extends FormActionPage
{
    private array $errors = [];
    private bool $passwordChanged = false;

    protected function formDataSent(): void
    {
        $password = filter_input(INPUT_POST, 'password');
        $newPassword = filter_input(INPUT_POST, 'newPassword');
        $newPassword2 = filter_input(INPUT_POST, 'newPassword2');

        if (!$newPassword) {
            $this->errors['newPassword'] = "Nové heslo nesmí být prázdné";
        }
        if ($newPassword !== $newPassword2) {
            $this->errors['newPassword2'] = "Nová hesla se musí shodovat";
        }

        $employee = $this->get_user();

        if (!$password) {
            $this->errors['password'] = "Heslo nesmí být prázdné";
        } elseif (!password_verify($password, $employee->password)) {
            $this->errors['password'] = "Heslo je nesprávné";
        }

        if (!$this->errors) {
            $employee->set_password($newPassword);
            $this->passwordChanged = $employee->update([Employee::PASSWORD]);
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            "changePasswordForm",
            [
                "errors"  => $this->errors,
                "success" => $this->passwordChanged
            ]
        );
    }
}

$page = new ChangePasswordActionPage();
$page->render();
