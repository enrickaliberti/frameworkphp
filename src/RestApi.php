<?php

namespace Framework;

class RestApi {
    private $db;
    private $router;
    private $apiPrefix;

    public function __construct(Database $db, Router $router, string $apiPrefix) {
        $this->db = $db;
        $this->router = $router;
        $this->apiPrefix = trim($apiPrefix, '/');
    }

    public function handleRequest($method, $uri) {
        // Normalizza l'URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $parts = explode('/', trim($uri, '/'));

        // Verifica se è una richiesta API
        $apiPrefixParts = explode('/', $this->apiPrefix);
        $isApiRequest = count($parts) >= count($apiPrefixParts) &&
            array_slice($parts, 0, count($apiPrefixParts)) === $apiPrefixParts;

        if ($isApiRequest) {
            $resource = $parts[count($apiPrefixParts)] ?? null;
            $action = $parts[count($apiPrefixParts) + 1] ?? null;
            $query = $_GET;

            if (!$resource) {
                http_response_code(400);
                return ['error' => 'Resource not specified', 'status' => 400];
            }

            // Gestione azione personalizzata tramite router
            $route = $this->router->match($method, $uri);
            if ($route) {
                $controllerName = 'Framework\\Controllers\\' . explode('@', $route['handler'])[0];
                $methodName = explode('@', $route['handler'])[1];

                if (!class_exists($controllerName)) {
                    http_response_code(404);
                    return ['error' => 'Controller not found', 'status' => 404];
                }

                $controller = new $controllerName($this->db);
                $params = $route['params'];
                return call_user_func_array([$controller, $methodName], $params);
            }

            // Gestione CRUD automatica
            return $this->handleCrud($method, $resource, $query);
        }

        // Gestione richieste non-API (view)
        $route = $this->router->match($method, $uri);
        if (!$route) {
            http_response_code(404);
            return ['error' => 'Route not found', 'status' => 404];
        }

        $controllerName = 'Framework\\Controllers\\' . explode('@', $route['handler'])[0];
        $methodName = explode('@', $route['handler'])[1];

        if (!class_exists($controllerName)) {
            http_response_code(404);
            return ['error' => 'Controller not found', 'status' => 404];
        }

        $controller = new $controllerName($this->db);
        $params = $route['params'];
        return call_user_func_array([$controller, $methodName], $params);
    }

    private function handleCrud($method, $resource, $query) {
        switch ($method) {
            case 'GET':
                if (empty($query)) {
                    return $this->db->find($resource);
                }

                $conditions = [];
                $likeConditions = [];
                foreach ($query as $key => $value) {
                    if (strpos($value, '%') !== false) {
                        $likeConditions[$key] = $value;
                    } else {
                        $conditions[$key] = $value;
                    }
                }

                if ($conditions || $likeConditions) {
                    $result = $this->db->find($resource, $conditions, $likeConditions);
                    return $result ?: ['error' => 'Resource not found', 'status' => 404];
                }

                return $this->db->find($resource);

            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    http_response_code(400);
                    return ['error' => 'Invalid input', 'status' => 400];
                }
                return ['id' => $this->db->create($resource, $data)];

            case 'PUT':
                if (!isset($query['id'])) {
                    http_response_code(400);
                    return ['error' => 'ID required', 'status' => 400];
                }
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    http_response_code(400);
                    return ['error' => 'Invalid input', 'status' => 400];
                }
                $success = $this->db->update($resource, $data, ['id' => $query['id']]);
                return ['success' => $success];

            case 'DELETE':
                if (!isset($query['id'])) {
                    http_response_code(400);
                    return ['error' => 'ID required', 'status' => 400];
                }
                $success = $this->db->delete($resource, ['id' => $query['id']]);
                return ['success' => $success];

            default:
                http_response_code(405);
                return ['error' => 'Method not allowed', 'status' => 405];
        }
    }
}