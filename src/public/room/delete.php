<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDeletePage extends CRUDPage
{
    use AdminAuthorization;
    protected function prepare(): void
    {
        parent::prepare();

        $roomId = filter_input(INPUT_POST, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();


        if ($roomId == $this->get_user()->room) {
            throw new ForbiddenException("Nemůžeš smazat svoji domovskou místnost");
        }

        $keysStmt = PDOProvider::get()->prepare("SELECT k.room FROM `key` k WHERE k.room = :roomId");
        $keysStmt->execute(['roomId' => $roomId]);
        if (!$keysStmt->rowCount() !== 0) {
            throw new ForbiddenException("Nemůžeš smazat místnost, ke které mají zaměstnanci klíče");
        }
        $keysStmt = PDOProvider::get()->prepare("SELECT e.room FROM `employee` e WHERE e.room = :roomId");
        $keysStmt->execute(['roomId' => $roomId]);
        if (!$keysStmt->rowCount() !== 0) {
            throw new ForbiddenException("Nemůžeš smazat místnost, kterou mají někteří zaměstnanci jako domovskou");
        }
        //když poslal data
        $success = Room::deleteByID($roomId);


        //přesměruj
        $this->redirect(CrudAction::DELETE, $success);
    }

    protected function pageBody()
    {
        return "";
    }
}

$page = new RoomDeletePage();
$page->render();
