<?php

//namespace models;

class Employee
{
    public const DB_TABLE = "employee";
    public ?int $employee_id;
    public ?string $name;
    public ?string $surname;
    public ?string $job;
    public ?string $wage;
    public ?int $room;
    public ?int $admin;
    public ?string $login;
    public ?string $password;

    public const ID = 'employee_id';
    public const NAME = 'name';
    public const SURNAME = 'surname';
    public const JOB = 'job';
    public const WAGE = 'wage';
    public const ROOM = 'room';
    public const LOGIN = 'login';
    public const PASSWORD = 'password';
    public const ADMIN = 'admin';

    public const FIELDS_NO_ID = [
        self::NAME,
        self::SURNAME,
        self::JOB,
        self::WAGE,
        self::ROOM,
        self::LOGIN,
        self::PASSWORD,
        self::ADMIN,
    ];
    public const FIELDS = [
        self::ID,
        ...self::FIELDS_NO_ID
    ];

    private const UPDATE_QUERY = "UPDATE " . self::DB_TABLE
        . " SET `"
        . self::NAME . "` = :" . self::NAME . ", `" .
        self::JOB . "` = :" . self::JOB . ", `" .
        self::LOGIN . "` = :" . self::LOGIN .
        self::PASSWORD . "` = :" . self::PASSWORD .
        self::WAGE . "` = :" . self::WAGE .
        self::ADMIN . "` = :" . self::ADMIN .
        " WHERE `" . self::ID . "` = :" . self::ID;

    private const DELETE_QUERY = "DELETE FROM `" . self::DB_TABLE
        . "` WHERE `" . self::ID . "` = :" . self::ID;

    private const INSERT_QUERY =  "INSERT INTO " . self::DB_TABLE
        . "("
        . self::NAME . ','
        . self::JOB . ','
        . self::SURNAME . ','
        . self::LOGIN . ','
        . self::PASSWORD . ','
        . self::WAGE . ','
        . self::ADMIN . ') VALUES ('
        . ':' . self::NAME . ','
        . ':' . self::JOB . ','
        . ':' . self::SURNAME . ','
        . ':' . self::LOGIN . ','
        . ':' . self::PASSWORD . ','
        . ':' . self::WAGE . ')';


    public function __construct(?int $employee_id = null, ?bool $admin = null, ?string $name = null, ?string $surname = null, ?string $job = null, ?string $wage = null, ?int $room = null, ?string $login = null, ?string $password = null)
    {
        $this->employee_id = $employee_id;
        $this->name = $name;
        $this->surname = $surname;
        $this->job = $job;
        $this->wage = $wage;
        $this->room = $room;
        $this->login = $login;
        $this->password = $password;
        $this->admin = $admin;
    }

    private function get_fields(bool $include_id)
    {
        return [
            ...($include_id ? [self::ID => $this->employee_id] : []),
            self::NAME => $this->name,
            self::LOGIN => $this->login,
            self::JOB => $this->job,
            self::PASSWORD => $this->password,
            self::ROOM => $this->room,
            self::SURNAME => $this->surname,
            self::ADMIN => $this->admin,
            self::WAGE => $this->wage,
        ];
    }

    public static function findByID(int $id): ?self
    {
        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "` WHERE `" . self::ID . "`= :" . self::ID);
        $stmt->execute([self::ID => $id]);

        if ($stmt->rowCount() < 1)
            return null;

        $employee = new self();
        $employee->hydrate($stmt->fetch());
        return $employee;
    }

    /**
     * @return self[]
     */
    public static function getAll($sorting = []): array
    {
        $sortSQL = "";
        if (count($sorting)) {
            $SQLchunks = [];
            foreach ($sorting as $field => $direction)
                $SQLchunks[] = "`{$field}` {$direction}";

            $sortSQL = " ORDER BY " . implode(', ', $SQLchunks);
        }

        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "`" . $sortSQL);
        $stmt->execute([]);

        $employees = [];
        while ($employeeData = $stmt->fetch()) {
            $employee = new self();
            $employee->hydrate($employeeData);
            $employees[] = $employee;
        }

        return $employees;
    }

    private function hydrate(array|object $data)
    {
        if (is_array($data)) {
            foreach (self::FIELDS as $field) {
                if (array_key_exists($field, $data))
                    $this->{$field} = $data[$field];
            }
        } else {
            foreach (self::FIELDS as $field) {
                if (property_exists($data, $field))
                    $this->{$field} = $data->{$field};
            }
        }
    }

    public function insert(): bool
    {
        $stmt = PDOProvider::get()->prepare(self::INSERT_QUERY);
        $result = $stmt->execute($this->get_fields(false));
        if (!$result)
            return false;

        $this->employee_id = PDOProvider::get()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if (!isset($this->employee_id) || !$this->employee_id)
            throw new Exception("Cannot update model without ID");

        $stmt = PDOProvider::get()->prepare(self::UPDATE_QUERY);
        return $stmt->execute($this->get_fields(true));
    }

    public function delete(): bool
    {
        return self::deleteByID($this->employee_id);
    }

    public static function deleteByID(int $employeeId): bool
    {
        $stmt = PDOProvider::get()->prepare(self::DELETE_QUERY);
        return $stmt->execute([self::ID => $employeeId]);
    }

    public function validate(&$errors = []): bool
    {
        if (!isset($this->room))
            $errors[self::ROOM] = 'Místnost musí být vyplněna';

        if (!isset($this->login) || !$this->login)
            $errors[self::LOGIN] = 'Login musí být vyplněn';

        if (!isset($this->password) || !$this->password)
            $errors[self::PASSWORD] = 'Password musí být vyplněno';

        if (!isset($this->job) || !$this->job)
            $errors[self::JOB] = 'Job musí být vyplněna';

        if (!isset($this->wage))
            $errors[self::WAGE] = 'Wage musí být vyplněna';
        elseif ($this->wage < 0) {
            $errors[self::WAGE] = 'Wage musí být větší nebo rovno 0';
        }

        if (!isset($this->admin)) {
            $errors[self::ADMIN] = 'Admin musí být vyplněn';
        }

        if (!isset($this->surname) || !$this->surname)
            $errors[self::SURNAME] = 'Surname musí být vyplněno';

        if (!isset($this->name) || !$this->name)
            $errors[self::NAME] = 'Name musí být vyplněno';

        return count($errors) === 0;
    }

    private static function readPostStr($var_name): mixed
    {
        $res = Utils::filter_input_null_fail(INPUT_POST, $var_name);
        if ($res) {
            $res = trim($res);
        }
        return $res;
    }



    public static function readPost(): self
    {
        $employee = new self();
        $employee->employee_id = Utils::filter_input_null_fail(INPUT_POST, self::ID, FILTER_VALIDATE_INT);

        $employee->name = self::readPostStr(self::NAME);

        $employee->surname = self::readPostStr(self::SURNAME);

        $employee->login = self::readPostStr(self::LOGIN);

        $employee->password = self::readPostStr(self::PASSWORD);

        $employee->admin = Utils::filter_input_null_fail(INPUT_POST, self::ADMIN, FILTER_VALIDATE_BOOL);

        $employee->wage = Utils::filter_input_null_fail(INPUT_POST, self::WAGE, FILTER_VALIDATE_INT);

        $employee->job = self::readPostStr(self::JOB);

        $employee->room = Utils::filter_input_null_fail(INPUT_POST, self::ROOM, FILTER_VALIDATE_INT);

        return $employee;
    }
}
