<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeFormActionPage extends FormActionPage
{
    private ?Employee $employee;
    private $rooms;
    private $keys;
    private ?array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Založit nového zaměstnance";

        switch ($this->state) {
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
                if ($isOk) {
                    $this->keys = Utils::filter_input_integers_array(INPUT_POST, "keys");
                    if ($this->keys == false) {
                        $isOk = false;
                        $this->errors['keys'] = "Vybrány invalidní klíče";
                    }
                }

                if (!$isOk) {
                    $this->state = FormState::FORM_REQUESTED;
                } else {
                    //ulož je
                    $success = $this->employee->insert();
                    if ($this->keys) {
                        $success = $success && PDOProvider::get()->query(
                            "INSERT INTO `key` (`employee`,`room`) VALUES ({$this->employee->employee_id},"
                                . implode("),({$this->employee->employee_id},", $this->keys)
                                . ");"
                        );
                    }

                    //přesměruj
                    $this->redirect(CrudAction::INSERT, $success);
                }
                break;
        }
    }

    protected function pageBody()
    {
        $stmt = PDOProvider::get()->query("SELECT r.room_id AS id, r.room_id, r.name, r.no FROM room r");

        $this->rooms = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_OBJ);
        $activeKeys = [];
        $inactiveKeys = $this->rooms;
        if ($this->keys) {
            foreach ($this->keys as $key) {
                $activeKeys[$key] = $this->rooms[$key];
            }
            $inactiveKeys = array_diff_key($this->rooms, $activeKeys);
        }
        $activeRoom = null;
        if (array_key_exists($this->employee->room, $this->rooms)) {
            $activeRoom = $this->rooms[$this->employee->room];
            unset($this->rooms[$this->employee->room]);
        }


        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'employee' => $this->employee,
                'inactiveRooms' => array_values($this->rooms),
                'activeRoom' => $activeRoom,
                'activeKeys' => array_values($activeKeys),
                'inactiveKeys' => array_values($inactiveKeys),
                'errors' => $this->errors
            ]
        );
    }
}

$page = new EmployeeFormActionPage();
$page->render();
