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
    public ?int $admin = 0;
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

    public const FIELDS_NO_ID_PASSWORD = [
        self::NAME,
        self::SURNAME,
        self::JOB,
        self::WAGE,
        self::ROOM,
        self::LOGIN,
        self::ADMIN,
    ];

    public const FIELDS_NO_ID = [
        ...self::FIELDS_NO_ID_PASSWORD,
        self::PASSWORD,
    ];
    public const FIELDS_NO_PASSWORD = [
        self::ID,
        ...self::FIELDS_NO_ID_PASSWORD
    ];
    public const FIELDS = [
        self::ID,
        ...self::FIELDS_NO_ID
    ];

    private static function get_delete_query()
    {
        return "DELETE FROM `" . self::DB_TABLE
            . "` WHERE `" . self::ID . "` = :" . self::ID;
    }

    private function get_update_query()
    {

        $fieldsStr = implode(",", array_map(function ($value) {
            return "`{$value}` = :{$value}";
        }, ($this->password ? self::FIELDS_NO_ID : self::FIELDS_NO_ID_PASSWORD)));

        return "UPDATE " . self::DB_TABLE . " SET " . $fieldsStr . " WHERE `" . self::ID . "` = :" . self::ID;
    }

    private static function get_insert_query()
    {
        $fieldsStr = "(`" . implode("`,", self::FIELDS_NO_ID) . "`)";

        return "INSERT INTO " . self::DB_TABLE
            . $fieldsStr . "VALUES "
            . $fieldsStr;
    }


    public function __construct(?int $employee_id = null, bool $admin = false, ?string $name = null, ?string $surname = null, ?string $job = null, ?string $wage = null, ?int $room = null, ?string $login = null, ?string $password = null)
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

    private function get_fields($fields)
    {
        $fieldsData = [];
        foreach ($fields as $field) {
            $fieldsData[$field] = $this->{$field};
        }
        return $fieldsData;
    }

    public static function findByID(int $id): ?self
    {
        $pdo = PDOProvider::get();
        $query =  "SELECT * FROM `" . self::DB_TABLE . "` WHERE `" . self::ID . "`= :" . self::ID;
        $stmt = $pdo->prepare($query);
        $stmt->execute([self::ID => $id]);

        if ($stmt->rowCount() < 1)
            return null;

        $employee = new self();
        $employee->hydrate($stmt->fetch());
        $employee->employee_id = $id;
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
        $stmt = PDOProvider::get()->prepare(self::get_insert_query());
        $result = $stmt->execute($this->get_fields(self::FIELDS_NO_ID));
        if (!$result)
            return false;

        $this->employee_id = PDOProvider::get()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if (!isset($this->employee_id) || !$this->employee_id)
            throw new Exception("Cannot update model without ID");

        $stmt = PDOProvider::get()->prepare($this->get_update_query());
        return $stmt->execute($this->get_fields($this->password ? self::FIELDS : self::FIELDS_NO_PASSWORD));
    }

    public function set_password($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function delete(): bool
    {
        return self::deleteByID($this->employee_id);
    }

    public static function deleteByID(int $employeeId): bool
    {
        $stmt = PDOProvider::get()->prepare(self::get_delete_query());
        return $stmt->execute([self::ID => $employeeId]);
    }

    public function validate(&$errors = [], bool $passwordRequired = true): bool
    {
        if (!isset($this->room))
            $errors['room'] = 'Místnost musí být vyplněna';

        if (!isset($this->login) || !$this->login)
            $errors['login'] = 'Login musí být vyplněn';

        if ($passwordRequired && (!isset($this->password) || !$this->password))
            $errors['password'] = 'Heslo musí být vyplněno';

        if (!isset($this->job) || !$this->job)
            $errors['job'] = 'Pozice musí být vyplněna';

        if (!isset($this->wage))
            $errors['wage'] = 'Plat musí být vyplněn';
        elseif ($this->wage < 0) {
            $errors['wage'] = 'Plat musí být větší nebo rovno 0';
        }

        if ($this->admin === null) {
            $errors['admin'] = 'Admin má neplatnou hodnotu';
        }

        if (!isset($this->surname) || !$this->surname)
            $errors['surname'] = 'Příjmení musí být vyplněno';

        if (!isset($this->name) || !$this->name)
            $errors['name'] = 'Jméno musí být vyplněno';

        return count($errors) === 0;
    }

    private static function readPostStr($var_name): mixed
    {
        $res = filter_input(INPUT_POST, $var_name);
        if ($res) {
            $res = trim($res);
        }
        return $res;
    }



    public static function readPost(): self
    {
        $employee = new self();
        $employee->employee_id = filter_input(INPUT_POST, self::ID, FILTER_VALIDATE_INT);
        $employee->name = self::readPostStr(self::NAME);

        $employee->surname = self::readPostStr(self::SURNAME);

        $employee->login = self::readPostStr(self::LOGIN);

        $employee->password = self::readPostStr(self::PASSWORD);
        if ($employee->password) {
            $employee->password = password_hash($employee->password, PASSWORD_DEFAULT);
        }

        $employee->admin  = filter_input(INPUT_POST, "admin", FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        $employee->wage = Utils::filter_input_null_fail(INPUT_POST, self::WAGE, FILTER_VALIDATE_INT);

        $employee->job = self::readPostStr(self::JOB);

        $employee->room = Utils::filter_input_null_fail(INPUT_POST, self::ROOM, FILTER_VALIDATE_INT);

        return $employee;
    }
}
