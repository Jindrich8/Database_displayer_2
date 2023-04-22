<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomUpdatePage extends FormActionPage
{
    use AdminAuthorization;

    private ?Room $room;
    private ?array $errors = [];

    public function __construct()
    {
        $this->title = "Upravit místnost";
    }

    protected function formRequested(): void
    {
        $roomId = filter_input(INPUT_GET, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();

        //jdi dál
        $this->room = Room::findByID($roomId);
        if (!$this->room)
            throw new NotFoundException();
    }

    protected function formDataSent(): void
    {
        //načti je
        $this->room = Room::readPost();

        //zkontroluj je, jinak formulář
        $this->errors = [];
        $isOk = $this->room->validate($this->errors);
        if (!$isOk) {
            $this->state = FormState::FORM_REQUESTED;
        } else {
            //ulož je
            $success = $this->room->update();

            //přesměruj
            Utils::redirect(Action::UPDATE, Model::ROOM, $this->room->room_id, $success ? null : ErrorCode::Uknown);
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

$page = new RoomUpdatePage();
$page->render();
