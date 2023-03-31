<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeesPage extends ListPage
{
    public function __construct()
    {
        $this->title = "Výpis zaměstnanců";
    }

    protected function getMessage(CrudAction $action, bool $success): string
    {
        switch ($action) {
            case CrudAction::INSERT:
                return "Vtvoření zaměstnance bylo " . $success ? "úspěšné" : "neúspěšné";
            case CrudAction::UPDATE:
                return "Úprava zaměstnance byla " . $success ? "úspěšná" : "neúspěšná";
            case CrudAction::DELETE:
                return "Smazání zaměstnance bylo " . $success ? "úspěšné" : "neúspěšné";
        }
        return "Neznámá " . ($success ? "úspěšná" : "neúspěšná") . "akce";
    }

    protected function getData(): string
    {
        //získat data
        $employees = Employee::getAll(['name' => 'ASC']);
        //prezentovat data
        return MustacheProvider::get()->render('employeeList', ['employees' => $employees]);
    }
}

$page = new EmployeesPage();
$page->render();
