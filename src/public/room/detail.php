<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDetailPage extends DetailPage
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

        $stmt = Utils::select(
            PDOProvider::get(),
            columns: [Employee::SURNAME, Employee::NAME, Employee::ID],
            from: Employee::DB_TABLE,
            where: '`' . Employee::ROOM . "`={$this->room->room_id}",
            sorting: [Employee::SURNAME => 'ASC', Employee::NAME => 'ASC']
        );
        $this->employees = $stmt->fetchAll();

        $this->title = "Detail místnosti {$this->room->no}";
    }

    protected function pageBody()
    {
        //prezentovat data
        return MustacheProvider::get()->render(
            'roomDetail',
            [
                'room' => $this->room,
                'employees' => $this->employees,
                'userIsAdmin' => $this->get_user()->admin
            ]
        );
    }
}

$page = new RoomDetailPage();
$page->render();
