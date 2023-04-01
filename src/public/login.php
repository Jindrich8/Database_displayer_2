<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";



class LoginFormActionPage extends BasePage
{
    private FormState $state;
    private array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->state = Utils::findFormState();

        switch ($this->state) {

            case FormState::DATA_SENT:
                $login = filter_input(INPUT_POST, 'login');
                $password = filter_input(INPUT_POST, 'password');

                if (!$login) {
                    $this->errors['login'] = "Jméno nesmí být prázdné";
                }
                if (!$password) {
                    $this->errors['password'] = "Heslo nesmí být prázdné";
                }

                if (!$this->errors) {
                    $stmt = PDOProvider::get()->prepare(
                        "SELECT `" . Employee::ID
                            . '`,`' . Employee::PASSWORD
                            . '`,`' . Employee::ADMIN
                            . "` FROM `" . Employee::DB_TABLE . "` WHERE `" . Employee::LOGIN . "` = :username"
                    );

                    $stmt->execute(['username' => $login]);
                    $user = $stmt->fetch();
                    if (!$user || !password_verify($password, $user->password)) {
                        $this->errors['error'] = "Jméno nebo heslo je nesprávné!";
                    } else {
                        $_SESSION['id'] = $user->employee_id;
                        header("Location: index.php");
                    }
                }
                if ($this->errors) {
                    $this->state = FormState::FORM_REQUESTED;
                }
                break;
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            "loginForm",
            ["errors"  => $this->errors]
        );
    }
}

$page = new LoginFormActionPage();
$page->render();
