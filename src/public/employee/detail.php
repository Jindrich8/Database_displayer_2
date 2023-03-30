<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDetailPage extends BasePage
{
    private $employee;
    private $employees;

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


        $stmt = PDOProvider::get()->prepare("SELECT r.employee_id, r.name, r.no, r.phone, e.surname, e.name, e.employee_id FROM employee e JOIN employee r WHERE `employee`= :employeeId ORDER BY e.surname, e.name");
        $stmt->execute(['employeeId' => $employeeId]);
        $this->employees = $stmt->fetchAll();

        $this->title = "Detail místnosti {$this->employee->no}";

    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'employeeDetail',
            ['employee' => $this->employee, 'employees' => $this->employees]
        );
    }

}

$page = new EmployeeDetailPage();
$page->render();

?>
