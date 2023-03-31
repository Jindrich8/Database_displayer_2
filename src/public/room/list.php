<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomsPage extends ListPage
{
    public function __construct()
    {
        $this->title = "Výpis místností";
    }

    protected function getMessage(CrudAction $action, bool $success): string
    {
        switch ($action) {
            case CrudAction::INSERT:
                return "Založení místnosti bylo " . $success ? "úspěšné" : "neúspěšné";
            case CrudAction::UPDATE:
                return "Úprava místnosti byla " . $success ? "úspěšná" : "neúspěšná";
            case CrudAction::DELETE:
                return "Smazání místnosti bylo " . $success ? "úspěšné" : "neúspěšné";
        }
        return "Neznámá " . ($success ? "úspěšná" : "neúspěšná") . "akce";
    }

    protected function getData():string{
        //získat data
        $rooms = Room::getAll(['name' => 'ASC']);
        //prezentovat data
        return MustacheProvider::get()->render('roomList',['rooms' => $rooms]);
    }

}

$page = new RoomsPage();
$page->render();
