<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeCreatePage extends CreatePage
{
    private ?Employee $employee;
    private ?array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Založit nového zaměstnance";

        switch ($this->state) {
                //když chce formulář
            case FormState::FORM_REQUESTED:
                $this->employee = new Employee();
                break;

                //když poslal data
            case FormState::DATA_SENT:
                //načti je
                $this->employee = Employee::readPost();

                //zkontroluj je, jinak formulář
                $this->errors = [];
                $isOk = $this->employee->validate($this->errors);
                if (!$isOk) {
                    $this->state = self::STATE_FORM_REQUESTED;
                } else {
                    //ulož je
                    $success = $this->employee->insert();

                    //přesměruj
                    $this->redirect(self::ACTION_INSERT, $success);
                }
                break;
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'employee' => $this->employee,
                'errors' => $this->errors
            ]
        );
    }
}

$page = new EmployeeCreatePage();
$page->render();

?>
