<?php

namespace App\Core;

class Response
{
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    /**
     * Send redirect response
     */
    public static function redirect(string $url, int $code = 302): never
    {
        header("Location: $url", true, $code);
        exit;
    }
}
