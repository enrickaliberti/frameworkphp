<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php

// Autoload personalizzato come fallback
spl_autoload_register(function ($class) {
    $prefix = 'Framework\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Carica l'autoload di Composer se esiste
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
}

use Framework\Database;
use Framework\Router;
use Framework\RestApi;

// Configurazione
$config = require __DIR__ . '/config/database.php';
$db = new Database($config);
$router = new Router();

// Definiamo il prefisso API
$apiPrefix = 'api/v1';
$api = new RestApi($db, $router, $apiPrefix);

// Definizione delle route
$router->add('GET', "/{$apiPrefix}/users/getStatus/:param", 'UsersController@getStatus');
$router->add('GET', '/users/status/:param', 'UsersController@showStatusPage');

// Gestione della richiesta
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$response = $api->handleRequest($method, $uri);

// Output della risposta
if (is_array($response)) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('Content-Type: text/html');
    echo $response;
}