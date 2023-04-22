<?php
class Logger
{
    public static function log(mixed $error)
    {
        file_put_contents(__DIR__ . '/../errorlog.txt', '\n' . json_encode($error), FILE_APPEND);
    }
}
