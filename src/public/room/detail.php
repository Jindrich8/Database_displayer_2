<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDetailPage extends BaseLoggedInPage
{
    private $room;
    private $employees;

    protected function prepare(): void
    {
        parent::prepare();
        //získat data z GET
        $roomId = filter_input(INPUT_GET, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();

        //najít místnost v databázi
        $this->room = Room::findByID($roomId);
        if (!$this->room)
            throw new NotFoundException();


        $stmt = PDOProvider::get()->prepare("SELECT e.surname, e.name, e.employee_id FROM employee e WHERE e.room = :roomId ORDER BY e.surname, e.name");
        $stmt->execute(['roomId' => $roomId]);
        $this->employees = $stmt->fetchAll();

        $this->title = "Detail místnosti {$this->room->no}";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'roomDetail',
            ['room' => $this->room, 'employees' => $this->employees]
        );
    }
}

$page = new RoomDetailPage();
$page->render();
