<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeDeletePage extends BaseLoggedInPage
{
    use AdminAuthorization;

    protected function prepare(): void
    {
        parent::prepare();
        $errorCode = null;
        $employeeId = filter_input(INPUT_POST, 'employeeId', FILTER_VALIDATE_INT);
        if (!$employeeId) {
            throw new BadRequestException();
        }
        if ($employeeId === $this->get_user()->employee_id) {
            $errorCode = ErrorCode::YouCannotDeleteYourself;
        } else {
            if (!Employee::deleteByID($employeeId)) {
                $errorCode = ErrorCode::Uknown;
            }
        }
        //přesměruj
        Utils::redirect(Action::DELETE, Model::EMPLOYEE, $employeeId, $errorCode);
    }

    protected function pageBody()
    {
        return "";
    }
}

$page = new EmployeeDeletePage();
$page->render();
