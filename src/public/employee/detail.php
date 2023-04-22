<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDetailPage extends DetailPage
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
        $pdo = PDOProvider::get();
        $this->room = ($roomStmt =  Utils::select(
            $pdo,
            columns: ['name' => 'rName', 'room_id'],
            from: Room::DB_TABLE,
            fromAlias: 'r',
            conns: [new JoinConn(
                join: Employee::DB_TABLE,
                joinAlias: 'e',
                on: "r.`room_id`=e.`room`"
            )],
            where: "e.`" . Employee::ID . "`=$employeeId"
        )) ? $roomStmt->fetch() : false;

        $this->keys = ($keysStmt = Utils::select(
            $pdo,
            columns: ['room_id', 'name'],
            from: Room::DB_TABLE,
            fromAlias: 'r',
            conns: [new JoinConn(
                join: 'key',
                joinAlias: 'k',
                on: "r.room_id=k.room"
            )],
            where: "k.employee = $employeeId"
        )) ? $keysStmt->fetchAll() : false;

        $this->title = "Detail zaměstnance {$this->employee->name} {$this->employee->surname}";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'employeeDetail',
            [
                'employee' => $this->employee,
                'room' => $this->room,
                'keys' => $this->keys,
                "admin" => $this->employee->admin,
                "userIsAdmin" => $this->get_user()->admin
            ]
        );
    }
}

$page = new EmployeeDetailPage();
$page->render();
