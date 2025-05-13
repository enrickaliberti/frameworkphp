<?php

namespace Framework;

class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function match($method, $uri): ?array {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($this->pathToRegex($route['path']), $uri, $matches)) {
                array_shift($matches); // Rimuove il match completo
                return [
                    'handler' => $route['handler'],
                    'params' => $matches
                ];
            }
        }

        return null;
    }

    public function dispatch($method, $uri) {
        $route = $this->match($method, $uri);
        if ($route) {
            return $this->callHandler($route['handler'], $route['params']);
        }

        http_response_code(404);
        return ['error' => 'Route not found'];
    }

    private function pathToRegex($path) {
        $path = preg_replace('#:([\w]+)#', '([^/]+)', $path);
        return "#^$path$#";
    }

    private function callHandler($handler, $params) {
        list($controller, $method) = explode('@', $handler);
        $controller = "Framework\\Controllers\\$controller";
        $instance = new $controller();
        return call_user_func_array([$instance, $method], $params);
    }
}