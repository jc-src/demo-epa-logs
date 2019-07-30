<?php

/**
 * Class Response
 */
class Response
{
    const HTTP_OK = 200;
    const HTTP_NOT_FOUND = 404;
    const HTTP_BAD_PARAMS = 400;

    public static function return(array $data, int $status)
    {
        self::send(json_encode($data), $status);
    }

    private static function send(string $content, int $status)
    {
        header("Content-type: application/json; charset=utf-8");
        header('Status: ' . $status);
        echo $content;
        exit;
    }
}