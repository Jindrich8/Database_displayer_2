<?php
session_start();
require_once __DIR__ . "/../../bootstrap/bootstrap.php";

class EmployeeUpdatePage extends FormActionPage
{
    use AdminAuthorization;

    private ?Employee $employee;
    private ?array $errors = [];

    protected function prepare(): void
    {
        parent::prepare();
        $this->findState();
        $this->title = "Upravit zaměstnance";

        //když chce formulář
        switch ($this->state) {
            case FormState::FORM_REQUESTED:
                $employeeId = filter_input(INPUT_GET, 'employeeId', FILTER_VALIDATE_INT);
                if (!$employeeId)
                    throw new BadRequestException();

                //jdi dál
                $this->employee = $this->get_user();
                if (!$this->employee)
                    throw new NotFoundException();
                break;

                //když poslal data
            case FormState::DATA_SENT:
                //načti je
                $this->employee = Employee::readPost();

                $this->errors = [];
                //zkontroluj je, jinak formulář
                $isOk = $this->employee->validate($this->errors, false);
                $keys = [];
                if ($isOk) {
                    $keys = Utils::filter_input_integers_array(INPUT_POST, "keys");
                    if ($keys == false) {
                        $isOk = false;
                        $this->errors['keys'] = "Vybrány invalidní klíče";
                    } elseif (!in_array($this->employee->room, $keys)) {
                        $this->errors['keys'] = "Zaměstnanec musí mít alespoň klíč ke své místnosti";
                    }
                }

                if (!$isOk) {
                    $this->state = FormState::FORM_REQUESTED;
                } else {

                    //ulož je
                    $success = $this->employee->update()
                        && PDOProvider::get()->query(
                            "DELETE FROM `key` WHERE `key`.employee = {$this->employee->employee_id}"
                        )
                        && (!$keys || PDOProvider::get()->query(
                            "INSERT INTO `key` (`employee`,`room`) VALUES ({$this->employee->employee_id},"
                                . implode("),({$this->employee->employee_id},", $keys)
                                . ");"
                        ));

                    //přesměruj
                    $this->redirect(CrudAction::UPDATE, $success);
                }
                break;
        }
    }

    protected function pageBody()
    {
        $activeRoom = null;
        $inactiveRooms = null;
        $activeKeys = null;
        $inactiveKeys = null; {
            $stmt = PDOProvider::get()->query("SELECT r.room_id as id, r.room_id, r.name, r.no FROM room r");
            $rooms =  $stmt->fetchAll(PDO::FETCH_UNIQUE); {
                $keysStmt = PDOProvider::get()->prepare("SELECT r.room_id as id, r.room_id, r.name, r.no FROM room r JOIN `key` k ON r.room_id = k.room WHERE k.employee = :employeeId");
                $keysStmt->execute(['employeeId' => $this->employee->employee_id]);
                $keys = $keysStmt->fetchAll(PDO::FETCH_UNIQUE);
                $activeKeys = array_values($keys);
                $inactiveKeys = array_values(array_diff_key($rooms, $keys));
            }


            if ($this->employee->room !== null && array_key_exists($this->employee->room, $rooms)) {
                $activeRoom = $rooms[$this->employee->room];
                unset($rooms[$this->employee->room]);
            }
            $inactiveRooms = array_values($rooms);
        }

        return MustacheProvider::get()->render(
            'employeeForm',
            [
                'title' => $this->title,
                'employee' => $this->employee,
                'inactiveRooms' => $inactiveRooms,
                'activeRoom' => $activeRoom,
                'activeKeys' => $activeKeys,
                'inactiveKeys' => $inactiveKeys,
                'errors' => $this->errors,
                "passwordRequired" => false,
            ]
        );
    }
}

$page = new EmployeeUpdatePage();
$page->render();
