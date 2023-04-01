<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";

class ChangePasswordActionPage extends BaseLoggedInPage
{
    private FormState $state;
    private array $errors = [];
    private bool $passwordChanged = false;

    protected function prepare(): void
    {
        parent::prepare();
        $this->state = Utils::findFormState();

        switch ($this->state) {

            case FormState::DATA_SENT:

                $password = filter_input(INPUT_POST, 'password');
                $newPassword = filter_input(INPUT_POST, 'newPassword');

                if (!$password) {
                    $this->errors['password'] = "Heslo nesmí být prázdné";
                }
                if (!$newPassword) {
                    $this->errors['newPassword'] = "Nové heslo nesmí být prázdné";
                }

                if (!$this->errors) {
                    $employee = $this->get_user();

                    if (!password_verify($password, $employee->password)) {
                        $this->errors['password'] = "Heslo je nesprávné";
                    } else {
                        $employee->set_password($newPassword);
                        $employee->update([Employee::PASSWORD]);
                    }
                    if ($this->errors) {
                        $this->state = FormState::FORM_REQUESTED;
                    }
                    break;
                }
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
