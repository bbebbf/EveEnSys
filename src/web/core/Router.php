<?php
declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function __construct(private Request $req) {}

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[$method][] = ['pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(): void
    {
        $method = $this->req->method();
        $uri    = $this->req->uri();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                ($route['handler'])($params);
                return;
            }
        }

        // Check if path exists for other HTTP methods
        $otherMethods = array_diff(['GET', 'POST'], [$method]);
        foreach ($otherMethods as $other) {
            foreach ($this->routes[$other] ?? [] as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    http_response_code(405);
                    header('Allow: ' . $other);
                    echo '<h1>405 Method Not Allowed</h1>';
                    return;
                }
            }
        }

        // 404
        http_response_code(404);
        $errorView = APP_ROOT . '/views/errors/404.php';
        if (file_exists($errorView)) {
            include APP_ROOT . '/views/layout/header.php';
            include $errorView;
            include APP_ROOT . '/views/layout/footer.php';
        } else {
            echo '<h1>404 Not Found</h1>';
        }
    }
}
