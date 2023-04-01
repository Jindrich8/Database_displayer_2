<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDetailPage extends BaseLoggedInPage
{
    private Employee $employee;
    private $room;
    private $keys;

    protected function prepare(): void
    {
        parent::prepare();
        //získat data z GET
        $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId)
            throw new BadRequestException();

        //najít místnost v databázi
        $this->employee = Employee::findByID($employeeId);
        if (!$this->employee)
            throw new NotFoundException();

        $roomStmt = PDOProvider::get()->prepare("SELECT r.name as rName, r.room_id FROM `key` k JOIN room r ON r.room_id = k.room WHERE k.employee = :employeeId");
        $roomStmt->execute(['employeeId' => $employeeId]);
        $this->room = $roomStmt->fetch();

        $stmt = PDOProvider::get()->prepare("SELECT r.room_id, r.name FROM room r JOIN `key` k ON r.room_id = k.room WHERE k.employee = :employeeId");
        $stmt->execute(['employeeId' => $employeeId]);
        $this->keys = $stmt->fetchAll();

        $this->title = "Detail zaměstnance {$this->employee->name} {$this->employee->surname}";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'employeeDetail',
            [
                'employee' => $this->employee,
                'room' => $this->room, 'keys' => $this->keys,
                "admin_bool" => $this->employee->admin ? 'true' : 'false'
            ]
        );
    }
}

$page = new EmployeeDetailPage();
$page->render();
