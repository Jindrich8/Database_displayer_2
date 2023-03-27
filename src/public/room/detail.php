<?php
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDetailPage extends BasePage
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


        $stmt = PDOProvider::get()->prepare("SELECT `surname`, `name`, `employee_id` FROM `employee` WHERE `room`= :roomId ORDER BY `surname`, `name`");
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

?>
