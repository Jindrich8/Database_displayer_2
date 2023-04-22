<?php
class Utils
{
    public static function filter_input_null_fail(
        int $type,
        string $var_name,
        int $filter = FILTER_DEFAULT,
        array|int $options = 0
    ): mixed {
        $res = filter_input($type, $var_name, $filter, $options);
        return $res === false ? null : $res;
    }

    public static function str_empty(string|null $str)
    {
        return $str === "";
    }

    public static function filter_input_integers_array(int $type, string $var_name): mixed
    {
        $keys = filter_input($type, $var_name, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);
        if ($keys) {
            $keys =  filter_var_array($keys, FILTER_VALIDATE_INT);
        }
        return $keys;
    }

    public static function validate_name(mixed $name, string $fieldName, NameValidation $type): ?string
    {
        if (!isset($name)) {
            return $fieldName . " nesmí být prázdné";
        } else {
            switch ($type) {
                case NameValidation::PERSON_NAME:
                    if (!preg_match('/^[\p{L}]+$/u', $name)) {
                        return "$fieldName smí obsahovat pouze písmena";
                    }
                    break;

                case NameValidation::USER_NAME:
                    if (!preg_match('/^(?:[\p{L}]+[\p{L}0-9-_]*|[0-9-_]+[\p{L}])$/u', $name)) {
                        return "$fieldName musí obsahovat alespoň jedno písmeno a libovolný počet čísel, - a _";
                    }
                    break;

                default:

                    throw new Exception("Validation type '" . json_encode($type) . "' is not supported!");
            }
        }

        return null;
    }

    public static function safe_query(PDO $pdo, string $query, bool &$success)
    {
        $stmt = false;
        try {
            $stmt = $pdo->query($query);
            $success = $stmt ? true : false;
            return $stmt;
        } catch (PDOException $e) {
            $success = false;
            if (AppConfig::get('debug'))
                throw $e;
        }
    }

    public static function findFormState(): FormState
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ?
            FormState::DATA_SENT
            : FormState::FORM_REQUESTED;
    }

    public static function redirect(Action $action, Model $model, ?int $id, ?ErrorCode $errorCode): void
    {
        $data = [
            'action' => $action->value + ($model->value << 2)
        ];
        if ($errorCode !== null) {
            $data['errorCode'] = $errorCode->value;
        }
        if ($id !== null) {
            $data['id'] = $id;
        }
        Utils::redirect_to_page('list.php?' . http_build_query($data));
    }

    public const CZ_PHONE_REGEX = '/^(?:\+(?<p>[0-9]+)|\(\s*\+(?<p>[0-9]+)\s*\))?\s*(?<n1>[0-9]{3})\s*(?<n2>[0-9]{3})\s*(?<n3>[0-9]{3})$/J';

    public static function is_valid_cz_phone_number(string $phone): bool
    {
        return preg_match(self::CZ_PHONE_REGEX, $phone, $matches);
    }

    public static function format_cz_phone_number(string $phone, bool $throw = true): ?string
    {
        $res = null;
        $matches = [];
        if (preg_match('/^(?<p>[0-9]+)(?<n1>[0-9]{3})(?<n2>[0-9]{3})(?<n3>[0-9]{3})$/J', $phone, $matches)) {
            $res = "";
            if ($matches['p']) {
                $res = "(+{$matches['p']}) ";
            }
            $res .=  "{$matches['n1']} {$matches['n2']} {$matches['n3']}";
        } elseif ($throw) {
            throw new Exception("Phone number is not in correct format");
        }
        return $res;
    }

    public static function str_null_or_empty(?string $value): bool
    {
        return $value === null || $value === "";
    }

    public static function extract_cz_phone_number(string $phone, bool $throw = true): ?string
    {
        $matches = [];
        if (preg_match(self::CZ_PHONE_REGEX, $phone, $matches)) {
            if (self::str_null_or_empty($matches['p'])) {
                $matches['p'] = "420";
            }
            $res = $matches['p'] . $matches['n1'] . $matches['n2'] . $matches['n3'];
            return $res;
        }
        if ($throw) {
            throw new Exception("Phone number is not in correct format");
        }
        return null;
    }

    public static function redirect_to_page(string $path)
    {
        header("Location: $path");
        exit;
    }

    public static function get_last_operation(): CrudOperation
    {
        $crudAction = null;
        $model = null;
        $errorCode = null;
        $id = null;

        $action =  filter_input(INPUT_GET, 'action', FILTER_VALIDATE_INT);
        if ($action !== false && $action !== null) {
            $crudAction = Action::tryFrom($action & 3);
            $model = Model::tryFrom($action >> 2);
        }
        $code = filter_input(INPUT_GET, 'errorCode', FILTER_VALIDATE_INT);
        if ($code !== null && $code !== false) {
            $errorCode = ErrorCode::tryFrom($code);
        }
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id === false) {
            $id = null;
        }
        return new CrudOperation($crudAction, $model, $id, $errorCode);
    }

    public static function insert(PDO $pdo, string $into, array $array): bool
    {
        $success = false;
        [$keys, $values] = self::arraykv($array);

        $stmt = $pdo->prepare(
            "INSERT INTO `$into` (" . self::qcolstostr($keys) . ")VALUES("
                . str_repeat('?,', count($values) - 1) . "?)"
        );
        if ($stmt) {
            try {
                $success = $stmt->execute($values);
            } catch (PDOException $e) {
                $success = false;
                if (AppConfig::get('debug'))
                    throw $e;
            }
        }
        return $success;
    }

    public static function update(PDO $pdo, string $table, array $array, ?string $where = null): bool
    {
        $success = false;
        [$keys, $values] = self::arraykv($array);

        $stmt = $pdo->prepare(
            "UPDATE `$table` SET `" . implode("`=?,`", $keys) . "`=? WHERE $where"
        );
        if ($stmt) {
            try {
                $success = $stmt->execute($values);
            } catch (PDOException $e) {
                $success = false;
                if (AppConfig::get('debug'))
                    throw $e;
            }
        }
        return $success;
    }

    public static function delete(PDO $pdo, string $table, string $where): bool
    {
        try {
            return $pdo->query("DELETE FROM `$table` WHERE $where") ? true : false;
        } catch (PDOException $e) {
            if (AppConfig::get('debug'))
                throw $e;
            return false;
        }
    }

    public static function qcolstostr(array $columns): string
    {
        return $columns ? '`' . implode("`,`", $columns) . '`' : "";
    }

    /**
     * @param string[] $columnswaliases
     */
    public static function qcolswaliasestostr(array $columnswaliases, string $table = null): string
    {
        if ($table) {
            $table = "`{$table}`.";
        }
        $str = "";
        foreach ($columnswaliases as $key => $value) {
            if ($str) {
                $str .= ',';
            }
            if (is_int($key)) {
                $str .= "$table`$value`";
            } else {
                $str .= "$table`$key` AS `$value`";
            }
        }
        return $str;
    }

    public static function qrawcolswaliasestostr(array $columnswaliases): string
    {
        $str = "";
        foreach ($columnswaliases as $key => $value) {
            if ($str) {
                $str .= ',';
            }
            if (is_int($key)) {
                $str .= "$value";
            } else {
                $str .= "$key AS `$value`";
            }
        }
        return $str;
    }

    public static function get_obj_props(object $obj, array $unset = []): array
    {
        $vars = get_object_vars($obj);
        foreach ($unset as $key) {
            unset($vars[$key]);
        }
        return $vars;
    }

    public static function arraykv(array $array): array
    {
        $keys = [];
        $values = [];
        foreach ($array as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }
        return [$keys, $values];
    }


    /**
     * @param string[] $columns
     * @param JoinConn[] $conns
     */
    public static function select(PDO $pdo, array $columns, string $from, string $fromAlias = null, ?array $conns = null, string $where = null, array $executeArgs = [], ?array $sorting = [], ?array $rawColumns = []): PDOStatement|false
    {

        $fromStr = "`$from`" . ($fromAlias ? " `$fromAlias`" : "");
        if ($conns && !$fromAlias) {
            $fromAlias = $from;
        }

        $columnsStr = self::qcolswaliasestostr($columns, $fromAlias);
        if ($rawColumns) {
            if ($columnsStr) {
                $columnsStr .= ',';
            }
            $columnsStr .= self::qrawcolswaliasestostr($rawColumns, $fromAlias);
        }
        if ($conns) {
            foreach ($conns as $connValue) {

                $fromStr .= "JOIN `{$connValue->join}`" . ($connValue->joinAlias ? " `{$connValue->joinAlias}`" : "");
                if (!$connValue->joinAlias) {
                    $connValue->joinAlias = $connValue->join;
                }
                $fromStr .= " ON {$connValue->on}";
                if ($connValue->columns) {
                    if ($columnsStr) {
                        $columnsStr .= ',';
                    }
                    $columnsStr .= self::qcolswaliasestostr($connValue->columns, $connValue->joinAlias);
                }
                if ($connValue->rawColumns) {
                    if ($columnsStr) {
                        $columnsStr .= ',';
                    }
                    $columnsStr .= self::qrawcolswaliasestostr($connValue->rawColumns, $connValue->joinAlias);
                }
            }
        }
        if (!$columnsStr) {
            $columnsStr = "*";
        }
        $query = "SELECT " . $columnsStr . " FROM " . $fromStr . ($where ? " WHERE $where" : '');
        if ($sorting) {
            $SQLchunks = [];
            foreach ($sorting as $field => $direction)
                $SQLchunks[] = "`{$field}` {$direction}";

            $query .= " ORDER BY " . implode(', ', $SQLchunks);
        }
        $stmt = null;
        if (!$executeArgs) {
            $stmt = $pdo->query($query);
        } elseif (($stmt = $pdo->prepare($query)) && !$stmt->execute($executeArgs)) {
            $stmt = false;
        }
        return $stmt;
    }
}
