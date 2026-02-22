<?php
declare(strict_types=1);

class ControllerTools
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function abort_Forbidden_403(): never
    {
       Self::abort(403); 
    }

    public static function abort_NotFound_404(): never
    {
       Self::abort(404); 
    }

    public static function abort(int $code): never
    {
        http_response_code($code);
        $errorView = APP_ROOT . '/views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            include APP_ROOT . '/views/layout/header.php';
            include $errorView;
            include APP_ROOT . '/views/layout/footer.php';
        } else {
            echo '<h1>' . $code . '</h1>';
        }
        exit;
    }
}