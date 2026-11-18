<?php
declare(strict_types=1);

namespace Nemr\Apex\Http;

use Closure;

/**
 * High-Performance REST Router with Middleware Pipeline (PHP 8.2 / 8.3)
 */
class Router
{
    protected array $routes = [];
    protected array $globalMiddleware = [];
    protected string $prefix = '';
    protected array $groupMiddleware = [];

    public function use(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $prevPrefix = $this->prefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->prefix = rtrim($prevPrefix . '/' . trim($prefix, '/'), '/');
        $this->groupMiddleware = array_merge($prevMiddleware, $middleware);

        $callback($this);

        $this->prefix = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    protected function addRoute(string $method, string $path, callable|array $handler, array $middleware): self
    {
        $fullPath = rtrim($this->prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $allMiddleware = array_merge($this->globalMiddleware, $this->groupMiddleware, $middleware);

        // Convert route parameters e.g. {id} into named regex groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $fullPath,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $allMiddleware,
        ];

        return $this;
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $trimmedUri = rtrim($parsedUri, '/');
        if ($trimmedUri === '') {
            $trimmedUri = '/';
        }

        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $trimmedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Build middleware execution onion pipeline
                $pipeline = array_reduce(
                    array_reverse($route['middleware']),
                    function ($next, $middleware) {
                        return function ($request) use ($middleware, $next) {
                            return $middleware($request, $next);
                        };
                    },
                    function ($request) use ($route, $params) {
                        $handler = $route['handler'];
                        if (is_callable($handler)) {
                            return $handler($request, $params);
                        }
                        if (is_array($handler) && count($handler) === 2) {
                            [$class, $action] = $handler;
                            $instance = is_object($class) ? $class : new $class();
                            return $instance->$action($request, $params);
                        }
                        throw new \RuntimeException("Invalid route handler format.");
                    }
                );

                $request = [
                    'method'  => $method,
                    'uri'     => $uri,
                    'path'    => $trimmedUri,
                    'params'  => $params,
                    'query'   => $_GET,
                    'body'    => json_decode(file_get_contents('php://input') ?: '[]', true) ?: $_POST,
                    'headers' => getallheaders() ?: [],
                ];

                return $pipeline($request);
            }
        }

        http_response_code(404);
        return [
            'error'   => 'Not Found',
            'message' => "Route [{$method}] {$trimmedUri} does not exist.",
            'code'    => 404
        ];
    }

    public function getRoutes(): array
    {
        return array_map(fn($r) => [
            'method'     => $r['method'],
            'path'       => $r['path'],
            'middleware' => count($r['middleware'])
        ], $this->routes);
    }
}
