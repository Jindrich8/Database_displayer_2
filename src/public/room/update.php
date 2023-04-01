<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomUpdatePage extends FormActionPage
{
    use AdminAuthorization;

    private ?Room $room;
    private ?array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Upravit místnost";

        //když chce formulář
        switch ($this->state) {
            case FormState::FORM_REQUESTED:
                $roomId = filter_input(INPUT_GET, 'roomId', FILTER_VALIDATE_INT);
                if (!$roomId)
                    throw new BadRequestException();

                //jdi dál
                $this->room = Room::findByID($roomId);
                if (!$this->room)
                    throw new NotFoundException();
                break;

                //když poslal data
            case FormState::DATA_SENT:
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
                    $this->redirect(CrudAction::UPDATE, $success);
                }
                break;
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
