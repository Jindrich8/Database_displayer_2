<?php
session_start();
require_once __DIR__ . "/../bootstrap/bootstrap.php";



class LoginFormActionPage extends BasePage
{
    private array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $id = $_SESSION['id'] ?? null;
        if ($id && Employee::findByID($id)) {
            Utils::redirect_to_page('/index.php');
        }
        switch (Utils::findFormState()) {

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
                    $stmt = Utils::select(
                        pdo: PDOProvider::get(),
                        columns: [Employee::ID, Employee::PASSWORD],
                        from: Employee::DB_TABLE,
                        where: ('`' . Employee::LOGIN . "`=?"),
                        executeArgs: [$login]
                    );
                    if ($stmt && ($user = $stmt->fetch()) && password_verify($password, $user->password)) {
                        $_SESSION['id'] = $user->employee_id;
                        Utils::redirect_to_page('/index.php');
                    } else {
                        $this->errors['error'] = "Jméno nebo heslo je nesprávné!";
                    }
                }
                break;
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            "loginForm",
            [
                "errors"  => $this->errors
            ]
        );
    }
}

$page = new LoginFormActionPage();
$page->render();
