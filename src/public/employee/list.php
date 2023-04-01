<?php
session_start();
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
                return "Vytvoření zaměstnance bylo " . ($success ? "úspěšné" : "neúspěšné");
            case CrudAction::UPDATE:
                return "Úprava zaměstnance byla " . ($success ? "úspěšná" : "neúspěšná");
            case CrudAction::DELETE:
                return "Smazání zaměstnance bylo " . ($success ? "úspěšné" : "neúspěšné");
        }
        return "Neznámá akce byla " . ($success ? "úspěšná" : "neúspěšná");
    }

    protected function getData(): string
    {
        //získat data
        $employees = Employee::getAll(['name' => 'ASC']);
        //prezentovat data
        return MustacheProvider::get()->render(
            'employeeList',
            ['employees' => $employees, 'isAdmin' => $this->get_user()->admin]
        );
    }
}

$page = new EmployeesPage();
$page->render();
