<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomFormActionPage extends FormActionPage
{
    use AdminAuthorization;

    private ?Room $room;
    private ?array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Založit novou místnost";

        switch ($this->state) {
                //když chce formulář
            case FormState::FORM_REQUESTED:
                //jdi dál
                $this->room = new Room();
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
                    $success = $this->room->insert();

                    //přesměruj
                    $this->redirect(CrudAction::INSERT, $success);
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

$page = new RoomFormActionPage();
$page->render();
