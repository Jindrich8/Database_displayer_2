<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeUpdatePage extends FormActionPage
{
    use AdminAuthorization;

    private ?Employee $employee;
    private ?array $errors = [];
    private ?array $keys = [];

    public function __construct()
    {
        $this->title = "Upravit zaměstnance";
    }

    protected function formRequested(): void
    {
        $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId)
            throw new BadRequestException();

        //jdi dál
        $this->employee = Employee::findById($employeeId);
        if (!$this->employee)
            throw new NotFoundException("Employee not found");

        $keysStmt = Utils::select(
            pdo: PDOProvider::get(),
            columns: [Room::ID],
            from: Room::DB_TABLE,
            fromAlias: 'r',
            conns: [new JoinConn('key', 'k', on: '`k`.`room`=`r`.`' . Room::ID . '`')],
            where: "`k`.`employee`={$this->employee->employee_id}"
        );
        $this->keys = $keysStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function formDataSent(): void
    {
        $user = $this->get_user();
        Employee::read_and_validate($this->employee, $this->errors, $this->keys, $user->employee_id, passwordRequired: false);

        if (!$this->errors) {
            //ulož je
            $errorCode = null;
            if (!$this->employee->update()) {
                $errorCode = ErrorCode::Uknown;
            }
            $pdo = PDOProvider::get();
            if ($errorCode === null) {
                $success = true;
                $success = Utils::delete($pdo, 'key', where: "`key`.`employee` = {$this->employee->employee_id}");
                if (!$success) {
                    $errorCode = ErrorCode::FailedToDeleteOldKeys;
                } elseif ($this->keys) {
                    $this->employee->insert_keys($this->keys, $success);
                    if (!$success) {
                        $errorCode = ErrorCode::FailedToInserNewKeys;
                    }
                }
            }

            //přesměruj
            Utils::redirect(Action::UPDATE, Model::EMPLOYEE, $this->employee->employee_id, $errorCode);
        }
    }

    protected function pageBody()
    {
        $activeKeys = [];
        $pdo = PDOProvider::get();
        $stmt = Utils::select(
            $pdo,
            columns: ['room_id' => 'id', 'room_id', 'name', 'no'],
            from: Room::DB_TABLE
        );
        $inactiveKeys = ($rooms =  $stmt->fetchAll(PDO::FETCH_UNIQUE));

        if ($this->keys) {
            foreach ($this->keys as $key) {
                if (array_key_exists($key, $rooms)) {
                    $activeKeys[$key] = $rooms[$key];
                }
            }
            $inactiveKeys = array_diff_key($rooms, $activeKeys);
        }

        $activeRoom = null;
        if ($this->employee->room !== null && array_key_exists($this->employee->room, $rooms)) {
            $activeRoom = $rooms[$this->employee->room];
            unset($rooms[$this->employee->room]);
        }
        $inactiveRooms = array_values($rooms);
        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'inactiveRooms' => $inactiveRooms,
                'activeRoom' => $activeRoom,
                'activeKeys' => array_values($activeKeys),
                'inactiveKeys' => array_values($inactiveKeys),
                'errors' => $this->errors,
                "passwordRequired" => false,
                "disableAdmin" => $this->get_user()->employee_id === $this->employee->employee_id
            ]
        );
    }
}

$page = new EmployeeUpdatePage();
$page->render();
