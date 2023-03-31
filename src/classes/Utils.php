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
}
