<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomFormActionPage extends FormActionPage
{
    use AdminAuthorization;

    private ?Room $room;
    private ?array $errors = [];

    public function __construct()
    {
        $this->title = "Založit novou místnost";
    }

    protected function formRequested(): void
    {
        $this->room = new Room();
    }

    protected function formDataSent(): void
    {
        //načti je
        $this->room = Room::readPost();

        //zkontroluj je, jinak formulář
        $this->errors = [];
        $isOk = $this->room->validate($this->errors);
        if ($isOk) {
            //ulož je
            $success = $this->room->insert();

            //přesměruj
            Utils::redirect(Action::CREATE, Model::ROOM, $this->room->room_id, $success ? null : ErrorCode::Uknown);
        }
    }

    protected function pageBody()
    {
        return MustacheProvider::get()->render(
            'roomForm',
            [
                'title' => $this->title,
                'room' => $this->room,
                'errors' => $this->errors
            ]
        );
    }
}

$page = new RoomFormActionPage();
$page->render();
