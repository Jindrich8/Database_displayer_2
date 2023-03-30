<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeUpdatePage extends CRUDPage
{
    private ?Employee $employee;
    private ?array $errors = [];
    private int $state;

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Upravit místnost";

        //když chce formulář
        if ($this->state === self::STATE_FORM_REQUESTED)
        {
            $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
            if (!$employeeId)
                throw new BadRequestException();

            //jdi dál
            $this->employee = Employee::findByID($employeeId);
            if (!$this->employee)
                throw new NotFoundException();

        }

        //když poslal data
        elseif($this->state === self::STATE_DATA_SENT) {
            //načti je
            $this->employee = Employee::readPost();

            //zkontroluj je, jinak formulář
            $this->errors = [];
            $isOk = $this->employee->validate($this->errors);
            if (!$isOk)
            {
                $this->state = self::STATE_FORM_REQUESTED;
            }
            else
            {
                //ulož je
               $success = $this->employee->update();

                //přesměruj
               $this->redirect(self::ACTION_UPDATE, $success);
            }
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

    private function findState() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            $this->state = self::STATE_DATA_SENT;
        else
            $this->state = self::STATE_FORM_REQUESTED;
    }

}

$page = new EmployeeUpdatePage();
$page->render();

?>
