<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeCreatePage extends CreatePage
{
    private ?Employee $employee;
    private $rooms;
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

                $stmt = PDOProvider::get()->query("SELECT r.room_id, r.name FROM room r");
                $this->rooms = $stmt->fetchAll();
                break;

                //když poslal data
            case FormState::DATA_SENT:
                //načti je
                $this->employee = Employee::readPost();

                $keys = filter_input(INPUT_POST, "keys", FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);

                //zkontroluj je, jinak formulář
                $this->errors = [];
                $isOk = $this->employee->validate($this->errors);
                if (!$isOk) {
                    $this->state = FormState::FORM_REQUESTED;
                } else {
                    //ulož je
                    $success = $this->employee->insert();

                    $query = "INSERT INTO `key` (`employee`,`room`) VALUES ({$this->employee->employee_id},"
                        . implode("),({$this->employee->employee_id},", $keys)
                        . ");";


                    $success = $success && PDOProvider::get()->query($query);

                    //přesměruj
                    $this->redirect(CrudAction::INSERT, $success);
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
                'rooms' => $this->rooms,
                'errors' => $this->errors
            ]
        );
    }
}

$page = new EmployeeCreatePage();
$page->render();
?>