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
            foreach ($keys as $key) {
                if (!filter_var($key, FILTER_VALIDATE_INT)) {
                    $keys = false;
                    break;
                }
            }
        }
        return $keys;
    }

    public static function findFormState(): FormState
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' ?
            FormState::DATA_SENT
            : FormState::FORM_REQUESTED;
    }
}
