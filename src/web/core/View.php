<?php
declare(strict_types=1);

class View
{
    public static function render(string $template, array $data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        extract($data, EXTR_SKIP);
        include APP_ROOT . '/views/layout/header.php';
        include APP_ROOT . '/views/' . $template . '.php';
        include APP_ROOT . '/views/layout/footer.php';
    }
}
