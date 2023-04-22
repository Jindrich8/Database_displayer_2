<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeCreatePage extends FormActionPage
{
    use AdminAuthorization;

    private ?Employee $employee = null;
    private array $keys = [];
    private array $errors = [];

    public function __construct()
    {
        $this->title = "Založit nového zaměstnance";
    }

    protected function formRequested(): void
    {
        $this->employee = new Employee();
    }

    protected function formDataSent(): void
    {
        Employee::read_and_validate($this->employee, $this->errors, $this->keys, $this->get_user()->employee_id);

        if (!$this->errors) {
            //ulož je
            $success = $this->employee->insert();
            if ($this->keys && $success) {
                $this->employee->insert_keys($this->keys, $success);
            }

            //přesměruj
            Utils::redirect(Action::CREATE, Model::EMPLOYEE, $this->employee->employee_id, $success ? null : ErrorCode::Uknown);
        }
    }

    protected function pageBody()
    {
        $stmt = Utils::select(
            pdo: PDOProvider::get(),
            columns: ['room_id' => 'id', 'room_id', 'name', 'no'],
            from: Room::DB_TABLE
        );
        $rooms = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_OBJ);
        $activeKeys = [];
        $inactiveKeys = $rooms;
        if ($this->keys) {
            foreach ($this->keys as $key) {
                $activeKeys[$key] = $rooms[$key];
            }
            $inactiveKeys = array_diff_key($rooms, $activeKeys);
        }
        $activeRoom = null;
        if (array_key_exists($this->employee->room, $rooms)) {
            $activeRoom = $rooms[$this->employee->room];
            unset($rooms[$this->employee->room]);
        }


        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'inactiveRooms' => array_values($rooms),
                'activeRoom' => $activeRoom,
                'activeKeys' => array_values($activeKeys),
                'inactiveKeys' => array_values($inactiveKeys),
                'errors' => $this->errors
            ]
        );
    }
}

$page = new EmployeeCreatePage();
$page->render();
