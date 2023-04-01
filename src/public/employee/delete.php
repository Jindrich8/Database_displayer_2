<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDeletePage extends CRUDPage
{
    use AdminAuthorization;

    protected function prepare(): void
    {
        parent::prepare();

        $employeeId = filter_input(INPUT_POST, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId)
            throw new BadRequestException();
        if ($employeeId === $_SESSION['id']) {
            throw new ForbiddenException("You cannot delete yourself");
        }

        //když poslal data
        $keysStmt = PDOProvider::get()->prepare("SELECT r.room_id FROM room r JOIN `key` k ON r.room_id = k.room WHERE k.employee = :employeeId");
        $keysStmt->execute(['employeeId' => $employeeId]);
        if ($keysStmt->rowCount() !== 0) {
            throw new ForbiddenException("Nemůžeš smazat zaměstnance, který má klíče");
        }
        $keys = $keysStmt->fetchAll(PDO::FETCH_UNIQUE);
        $success = Employee::deleteByID($employeeId);

        //přesměruj
        $this->redirect(CrudAction::DELETE, $success);
    }

    protected function pageBody()
    {
        return "";
    }
}

$page = new EmployeeDeletePage();
$page->render();
