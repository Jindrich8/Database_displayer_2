<?php

//namespace models;

class Employee
{
    public const DB_TABLE = "employee";
    public ?int $employee_id;
    public ?string $name;
    public ?string $surname;
    public ?string $job;
    public ?int $wage;
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

    public const FIELDS_NO_ID_PASSWORD_NAME_SURNAME = [

        self::JOB,
        self::WAGE,
        self::ROOM,
        self::LOGIN,
        self::ADMIN
    ];

    public const FIELDS = [
        self::ID,
        self::NAME,
        self::SURNAME,
        ...self::FIELDS_NO_ID_PASSWORD_NAME_SURNAME,
        self::PASSWORD
    ];




    public function __construct(?int $employee_id = null, bool $admin = false, ?string $name = null, ?string $surname = null, ?string $job = null, ?int $wage = null, ?int $room = null, ?string $login = null, ?string $password = null)
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

    public static function findByID(int $id): ?self
    {
        $employee = null;
        if ($stmt = Utils::select(pdo: PDOProvider::get(), columns: [], from: self::DB_TABLE, where: '`' . self::ID . "`=$id")) {
            if ($employeeData = $stmt->fetch()) {
                $employee = self::create($employeeData);
                $employee->employee_id = $id;
            }
        }
        return $employee;
    }

    public static function create($data): self
    {
        $employee = new self();
        $employee->hydrate($data);
        return $employee;
    }

    /**
     * @return self[]
     */
    public static function getAll($sorting = [], $columns = []): array
    {
        $stmt = Utils::select(pdo: PDOProvider::get(), columns: $columns, from: self::DB_TABLE, sorting: $sorting);

        $employees = [];
        if ($stmt) {
            while ($employeeData = $stmt->fetch()) {
                $employees[] = self::create($employeeData);
            }
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

    public static function read_and_validate(?Employee &$employee, ?array &$errors, ?array &$keys, int $userId, bool $passwordRequired = true)
    {
        $employee = Employee::readPost();
        $employee->validate($errors,$userId, passwordRequired: $passwordRequired);
        if (filter_input(INPUT_POST, 'password') !== filter_input(INPUT_POST, 'confirmPassword')) {
            $errors['confirmPassword'] = "Hesla se musí shodovat";
        }
        $keys = Utils::filter_input_integers_array(INPUT_POST, "keys");
        if ($keys === false) {
            $errors['keys'] = "Vybrány invalidní klíče";
        } elseif (!in_array($employee->room, $keys)) {
            $errors['keys'] = "Zaměstnanec musí mít alespoň klíč ke své místnosti";
        }
    }

    public function insert_keys(array $keys, bool &$success)
    {
        Utils::safe_query(
            PDOProvider::get(),
            "INSERT INTO `key` (`employee`,`room`) VALUES ({$this->employee_id},"
                . implode("),({$this->employee_id},", $keys)
                . ");",
            $success
        );
    }

    public function insert(): bool
    {
        $result = Utils::insert(PDOProvider::get(), self::DB_TABLE, Utils::get_obj_props($this, [self::ID]));
        if ($result) {
            $this->employee_id = PDOProvider::get()->lastInsertId();
        }
        return $result;
    }

    public static function update_by_id(array $values, int $id): bool
    {
        return Utils::update(
            PDOProvider::get(),
            self::DB_TABLE,
            $values,
            where: '`' . self::ID . "`={$id}"
        );
    }


    public function update(array $values = []): bool
    {
        if (!isset($this->employee_id) || !$this->employee_id)
            throw new Exception("Cannot update model without ID");

        if (!$values) {
            $unset = [self::ID];
            if (!$this->password) {
                $unset[] = self::PASSWORD;
            }
            $values = Utils::get_obj_props($this, $unset);
        } else {
            foreach ($values as $key => $value) {
                $values[$value] = $this->{$value};
                unset($values[$key]);
            }
        }
        return self::update_by_id($values, $this->employee_id);
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
        return Utils::delete(PDOProvider::get(), self::DB_TABLE, where: '`' . self::ID . "`=$employeeId");
    }



    public function validate(&$errors = [],int $userId, bool $passwordRequired = true): bool
    {
        $pdo = PdoProvider::get();
        if (!isset($this->room)) {
            $errors['room'] = 'Místnost musí být vyplněna';
        } elseif (
            ($stmt = Utils::select(
                pdo: $pdo,
                columns: [Room::ID],
                from: Room::DB_TABLE,
                fromAlias: 'r',
                where: "`r`.`" . Room::ID . "`={$this->room}"
            ))
            && $stmt->rowCount() !== 1
        ) {
            $errors['room'] = 'Místnost musí existovat';
        }

        if (($error = Utils::validate_name(
            $this->login,
            fieldName: 'Login',
            type: NameValidation::USER_NAME
        ))) {
            $errors['login'] = $error;
        } elseif (($stmt = Utils::select(
            pdo: $pdo,
            columns: [self::ID],
            from: self::DB_TABLE,
            where: '`' . self::LOGIN . "`=?" . ($this->employee_id !== null ? " AND `" . self::ID . "`!={$this->employee_id}" : ""),
            executeArgs: [$this->login]
        )) && $stmt->rowCount() !== 0) {
            $errors['login'] = "Login musí být unikátní";
        }

        if ($passwordRequired && Utils::str_null_or_empty($this->password)) {
            $errors['password'] = 'Heslo musí být vyplněno';
        }

        if (($error = Utils::validate_name(
            $this->job,
            fieldName: 'Pozice',
            type: NameValidation::USER_NAME
        ))) {
            $errors['job'] = $error;
        }

        if (!isset($this->wage))
            $errors['wage'] = 'Plat musí být vyplněn';
        elseif ($this->wage < 0) {
            $errors['wage'] = 'Plat musí být větší nebo rovno 0';
        }

        if ($userId === $this->employee_id) {
            if ((bool)$this->admin !== true) {
                $this->admin = true;
                $errors['admin'] = 'Nemůžeš měnit svojí roli';
            }
        } elseif ($this->admin === null) {
            $errors['admin'] = 'Admin má neplatnou hodnotu';
        }
        if (($error =  Utils::validate_name(
            $this->surname,
            fieldName: 'Příjmení',
            type: NameValidation::PERSON_NAME
        ))) {
            $errors['surname'] = $error;
        }
        if (($error =  Utils::validate_name(
            $this->name,
            fieldName: 'Jméno',
            type: NameValidation::PERSON_NAME
        ))) {
            $errors['name'] = $error;
        }

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
        if ($employee->login) {
        }

        $employee->password = self::readPostStr(self::PASSWORD);
        if ($employee->password) {
            $employee->password = password_hash($employee->password, PASSWORD_DEFAULT);
        }

        $employee->admin = filter_input(INPUT_POST, 'admin', FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        $employee->wage = Utils::filter_input_null_fail(INPUT_POST, self::WAGE, FILTER_VALIDATE_INT);

        $employee->job = self::readPostStr(self::JOB);

        $employee->room = Utils::filter_input_null_fail(INPUT_POST, self::ROOM, FILTER_VALIDATE_INT);

        return $employee;
    }
}
