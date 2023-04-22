<?php

//namespace models;

class Room
{
    public const DB_TABLE = "room";

    public ?int $room_id;
    public ?string $name;
    public ?string $no;
    public ?string $phone;

    const ID = 'room_id';
    const NAME = 'name';
    const NO = 'no';
    const PHONE = 'phone';

    public const FIELDS_NO_ID_NAME = [
        self::NO,
        self::PHONE
    ];

    /**
     * @param int|null $room_id
     * @param string|null $name
     * @param string|null $no
     * @param string|null $phone
     */
    public function __construct(?int $room_id = null, ?string $name = null, ?string $no = null, ?string $phone = null)
    {
        $this->room_id = $room_id;
        $this->name = $name;
        $this->no = $no;
        $this->phone = $phone;
    }

    public static function create($roomData)
    {
        $room = new self();
        $room->hydrate($roomData);
        return $room;
    }

    public static function findByID(int $id): ?self
    {
        $room = null;
        if ($stmt = Utils::select(pdo: PDOProvider::get(), columns: [], from: self::DB_TABLE, where: "`room_id`=$id")) {
            if ($roomData = $stmt->fetch()) {
                $room = self::create($roomData);
            }
        }
        return $room;
    }

    /**
     * @return Room[]
     */
    public static function getAll($sorting = []): array
    {
        $stmt = Utils::select(pdo: PDOProvider::get(), columns: [], from: self::DB_TABLE, sorting: $sorting);

        $rooms = [];
        if ($stmt) {
            while ($roomData = $stmt->fetch()) {
                $rooms[] = self::create($roomData);
            }
        }

        return $rooms;
    }



    public function getPhoneInDbFormat(): ?string
    {
        $phone = null;
        if ($this->phone) {
            $phone = Utils::extract_cz_phone_number($this->phone);
        }
        return $phone;
    }

    private function hydrate(array|object $data)
    {
        $fields = ['room_id', 'name', 'no', 'phone'];
        if (is_array($data)) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $data))
                    $this->{$field} = $data[$field];
            }
        } else {
            foreach ($fields as $field) {
                if (property_exists($data, $field))
                    $this->{$field} = $data->{$field};
            }
        }
        if (!Utils::str_null_or_empty($data->phone)) {
            $this->phone = Utils::format_cz_phone_number($this->phone);
        }
    }

    public function insert(): bool
    {
        $array = Utils::get_obj_props($this, ['room_id']);
        $this->prepare_for_database($array);
        $result = Utils::insert(
            PDOProvider::get(),
            self::DB_TABLE,
            $array
        );
        if ($result) {
            $this->room_id = PDOProvider::get()->lastInsertId();
        }
        return $result;
    }

    private static function prepare_for_database(array|Room &$data)
    {
        if (is_array($data)) {
            if (array_key_exists(self::PHONE, $data) && $data[self::PHONE]) {
                $data[self::PHONE] = Utils::extract_cz_phone_number($data[self::PHONE]);
            }
        } else {
            $data->phone = $data->getPhoneInDbFormat();
        }
    }

    public function update(): bool
    {
        if (!isset($this->room_id) || !$this->room_id)
            throw new Exception("Cannot update model without ID");

        $array = Utils::get_obj_props($this, [self::ID]);
        $this->prepare_for_database($array);

        return Utils::update(
            PDOProvider::get(),
            self::DB_TABLE,
            $array,
            where: '`' . self::ID . "`={$this->room_id}"
        );
    }

    public function delete(): bool
    {
        return self::deleteByID($this->room_id);
    }

    public static function deleteByID(int $roomId): bool
    {
        return Utils::delete(PDOProvider::get(), self::DB_TABLE, where: "`room_id`=$roomId");
    }

    public function validate(&$errors = []): bool
    {
        $pdo = PDOProvider::get();
        if (($error = Utils::validate_name(
            $this->name,
            'Název',
            NameValidation::USER_NAME
        ))) {
            $errors['name'] = $error;
        }

        $notRoomIdSql = $this->room_id !== null ? " AND `room_id`!={$this->room_id}" : "";


        if (!isset($this->no) || (!$this->no)) {
            $errors['no'] = 'Číslo musí být vyplněno';
        } elseif (!preg_match('/^[0-9]+$/', $this->no)) {
            $errors['no'] = "Číslo musí být číslem";
        } elseif (
            ($stmt = Utils::select(
                $pdo,
                columns: ['room_id'],
                from: self::DB_TABLE,
                where: '`no`=?' . $notRoomIdSql,
                executeArgs: [$this->no]
            ))
            && $stmt->rowCount() !== 0
        ) {
            $errors['no'] = 'Číslo musí být unikátní';
        }
        if ($this->phone !== null) {

            if (!Utils::is_valid_cz_phone_number($this->phone)) {
                $errors['phone'] = 'Telefon má nesprávný formát. Př.: +420 123 456 789';
            } elseif ((($stmt = Utils::select(
                $pdo,
                columns: ['room_id'],
                from: self::DB_TABLE,
                where: '`phone`=?' . $notRoomIdSql,
                executeArgs: [$this->getPhoneInDbFormat()]
            )) && $stmt->rowCount() !== 0)) {
                $errors['phone'] = 'Telefon musí být unikátní';
            }
        }

        return count($errors) === 0;
    }

    public static function readPost(): self
    {
        $room = new Room();
        $room->room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);

        $name = filter_input(INPUT_POST, 'name');
        if ($name !== null && $name !== false) {
            $room->name = trim($name);
        }

        $no = filter_input(INPUT_POST, 'no');
        if ($no !== false && $no !== null) {
            $room->no = trim($no);
        }

        $phone = filter_input(INPUT_POST, 'phone');
        if ($phone !== false && $phone !== null) {
            if (($phone = trim($phone)) !== "") {
                $room->phone = $phone;
            }
        }

        return $room;
    }
}
