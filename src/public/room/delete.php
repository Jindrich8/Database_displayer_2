<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class RoomDeletePage extends BaseLoggedInPage
{
    use AdminAuthorization;

    protected function prepare(): void
    {
        parent::prepare();

        $roomId = filter_input(INPUT_POST, 'roomId', FILTER_VALIDATE_INT);
        if (!$roomId)
            throw new BadRequestException();
        $errorCode = null;

        if ($roomId == $this->get_user()->room) {
            $errorCode = ErrorCode::YouCannotDeleteHomeRoom;
        } else {
            $keysStmt = Utils::select(
                PDOProvider::get(),
                columns: [Employee::ID],
                from: Employee::DB_TABLE,
                where: '`' . Employee::ROOM . "` = $roomId"
            );
            if ($keysStmt->rowCount() !== 0) {
                $errorCode = ErrorCode::YouCannotDeleteHomeRoom;
            } elseif (!Room::deleteByID($roomId)) {
                $errorCode = ErrorCode::Uknown;
            }
        }


        //přesměruj
        Utils::redirect(Action::DELETE, Model::ROOM, $roomId, $errorCode);
    }

    protected function pageBody()
    {
        return "";
    }
}

$page = new RoomDeletePage();
$page->render();
