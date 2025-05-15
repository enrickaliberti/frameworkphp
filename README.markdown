# PHP REST API Framework

This is a lightweight PHP framework for building RESTful APIs and web applications. It provides automatic CRUD operations, custom API routes, controller-based routing, and a simple database abstraction layer. The framework is designed to be flexible, allowing developers to configure the API prefix and extend functionality through custom controllers.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Setting the API Prefix](#setting-the-api-prefix)
- [CRUD API Functionality](#crud-api-functionality)
- [Custom API Routes](#custom-api-routes)
- [Controller Routes](#controller-routes)
- [Database Library](#database-library)
- [Testing the Framework](#testing-the-framework)
- [Directory Structure](#directory-structure)
- [Troubleshooting](#troubleshooting)

## Requirements
- PHP 7.4 or higher
- MySQL or MariaDB
- Apache with `mod_rewrite` enabled (or equivalent web server)
- PDO MySQL extension
- Composer (optional, for autoloading)

## Installation
1. **Clone or Download the Framework**:
   - Download the framework ZIP file or clone the repository to your server.
   - Extract it to your web server's document root (e.g., `/home/royalboatcharter.peer2shop.com/public_html/`).

2. **Set Up the Database**:
   - Create a MySQL database.
   - Import the `database.sql` schema to set up the `users` table:
     ```bash
     mysql -u your_user -p your_database < database.sql
     ```

3. **Configure the Database**:
   - Edit `config/database.php` with your database credentials:
     ```php
     <?php
     return [
         'host' => 'localhost',
         'dbname' => 'your_database_name',
         'user' => 'your_database_user',
         'pass' => 'your_database_password'
     ];
     ```

4. **Set Up File Permissions**:
   - Ensure the `public` directory is accessible by the web server.
   - Set appropriate permissions (e.g., `chmod 755 public`).

5. **Configure the Web Server**:
   - Ensure the `.htaccess` files in the root and `public` directories are present to route requests to `public/index.php`.
   - Root `.htaccess`:
     ```
     RewriteEngine On
     RewriteBase /
     RewriteCond %{REQUEST_URI} !^/public/
     RewriteRule ^(.*)$ public/index.php [QSA,L]
     ```
   - Public `.htaccess`:
     ```
     RewriteEngine On
     RewriteBase /
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule ^(.*)$ index.php [QSA,L]
     ```

6. **Optional: Install Composer Dependencies**:
   - If using Composer, run:
     ```bash
     composer install
     ```
   - The framework includes a fallback autoloader, so Composer is not strictly required.

## Configuration
- **Database**: Configure `config/database.php` with your MySQL credentials.
- **API Prefix**: Set the API prefix in `public/index.php` (see [Setting the API Prefix](#setting-the-api-prefix)).
- **Routes**: Define custom routes in `public/index.php` for APIs and controllers.

## Setting the API Prefix
The API prefix (e.g., `/api/v1`) is configurable in `public/index.php`. This allows you to change the base URL for all API endpoints without modifying the core framework.

1. Open `public/index.php`.
2. Locate the `$apiPrefix` variable:
   ```php
   $apiPrefix = 'api/v1';
   ```
3. Change it to your desired prefix (e.g., `api/v2`, `api`, etc.):
   ```php
   $apiPrefix = 'api/v2';
   ```
4. The prefix is passed to the `RestApi` class:
   ```php
   $api = new RestApi($db, $router, $apiPrefix);
   ```

All API routes will use this prefix. For example, with `$apiPrefix = 'api/v1'`, the CRUD endpoint for users is `GET /api/v1/users`.

## CRUD API Functionality
The framework provides automatic CRUD (Create, Read, Update, Delete) operations for any database table via RESTful API endpoints. These are handled by the `RestApi` class and require no additional coding for basic operations.

### Endpoints
Assuming the API prefix is `api/v1` and the resource is `users`:

- **Read All**:
  - **Request**: `GET /api/v1/users`
  - **Response**: Returns all records from the `users` table as JSON.
    ```json
    [
        {"id": 1, "name": "John Doe", "status": "active"},
        {"id": 2, "name": "Jane Doe", "status": "inactive"}
    ]
    ```

- **Read One**:
  - **Request**: `GET /api/v1/users?id=1`
  - **Response**: Returns the record with `id=1` or a 404 error if not found.
    ```json
    {"id": 1, "name": "John Doe", "status": "active"}
    ```

- **Create**:
  - **Request**: `POST /api/v1/users`
  - **Body**: JSON data (e.g., `{"name": "John Doe", "status": "active"}`)
  - **Response**: Returns the ID of the created record.
    ```json
    {"id": 3}
    ```

- **Update**:
  - **Request**: `PUT /api/v1/users?id=1`
  - **Body**: JSON data (e.g., `{"name": "John Smith", "status": "inactive"}`)
  - **Response**: Returns success status.
    ```json
    {"success": true}
    ```

- **Delete**:
  - **Request**: `DELETE /api/v1/users?id=1`
  - **Response**: Returns success status.
    ```json
    {"success": true}
    ```

### LIKE Queries
The framework supports SQL `LIKE` queries for filtering results using wildcards (`%`). For example:
- **Request**: `GET /api/v1/users/?name=Doe%`
  - Returns all users whose `name` starts with "Doe".
    ```json
    [
        {"id": 1, "name": "Doe, John", "status": "active"},
        {"id": 2, "name": "Doe, Jane", "status": "active"}
    ]
    ```
- **Request**: `GET /api/v1/users/?name=%Doe%&status=active`
  - Returns users whose `name` contains "Doe" and `status` is exactly "active".

**Note**: The `%` wildcard must be included in the query parameter value. Multiple conditions (exact and `LIKE`) can be combined.

## Custom API Routes
Custom API routes allow you to define endpoints with specific logic beyond automatic CRUD. These are handled by controllers and registered in `public/index.php`.

### Defining a Custom Route
1. Open `public/index.php`.
2. Add a route using the `Router` class, specifying the HTTP method, path, and controller handler:
   ```php
   $router->add('GET', "/{$apiPrefix}/users/getStatus/:param", 'UsersController@getStatus');
   ```
   - `GET`: HTTP method.
   - `/{$apiPrefix}/users/getStatus/:param`: Path with the API prefix and a dynamic parameter (`:param`).
   - `UsersController@getStatus`: Controller and method to handle the request.

3. The route will respond to requests like `GET /api/v1/users/getStatus/1`, invoking the `getStatus` method in `UsersController`.

### Example Controller
The `UsersController` defines the `getStatus` method:
```php
public function getStatus($param) {
    $results = $this->db->query("SELECT status FROM users WHERE id = :id", ['id' => $param]);
    $result = $results[0] ?? [];
    return ['status' => $result['status'] ?? 'unknown', 'param' => $param];
}
```
- **Request**: `GET /api/v1/users/getStatus/1`
- **Response**:
  ```json
  {"status": "active", "param": "1"}
  ```

## Controller Routes
Controller routes are used for non-API endpoints, typically to render HTML views. These routes are also defined in `public/index.php` and handled by controllers.

### Defining a Controller Route
1. Add a route in `public/index.php`:
   ```php
   $router->add('GET', '/users/status/:param', 'UsersController@showStatusPage');
   ```
   - `/users/status/:param`: Path without the API prefix (for HTML views).
   - `UsersController@showStatusPage`: Controller method to render the view.

2. The route responds to requests like `GET /users/status/1`, invoking `showStatusPage` in `UsersController`.

### Example Controller Method
The `showStatusPage` method renders an HTML view:
```php
public function showStatusPage($param) {
    $results = $this->db->query("SELECT status FROM users WHERE id = :id", ['id' => $param]);
    $result = $results[0] ?? [];
    $status = $result['status'] ?? 'unknown';
    
    ob_start();
    require __DIR__ . '/../../views/status.php';
    return ob_get_clean();
}
```
- **View (`views/status.php`)**:
  ```php
  <!DOCTYPE html>
  <html>
  <head><title>User Status</title></head>
  <body>
      <h1>User Status</h1>
      <p>ID: <?php echo htmlspecialchars($param); ?></p>
      <p>Status: <?php echo htmlspecialchars($status); ?></p>
  </body>
  </html>
  ```
- **Request**: `GET /users/status/1`
- **Response**: HTML page displaying the user’s ID and status.

## Database Library
The `Database` class (`src/Database.php`) provides a simple abstraction for MySQL operations using PDO. It supports querying, CRUD operations, and `LIKE` queries.

### Initialization
The `Database` class is instantiated with configuration from `config/database.php`:
```php
$config = require __DIR__ . '/../config/database.php';
$db = new Database($config);
```

### Methods
1. **query(string $sql, array $params = []): array**
   - Executes a custom SQL query with optional parameters.
   - Returns an array of results (via `fetchAll()`).
   - **Example**:
     ```php
     $results = $db->query("SELECT * FROM users WHERE id = :id", ['id' => 1]);
     // Returns: [{"id": 1, "name": "John Doe", "status": "active"}]
     ```

2. **find(string $table, array $conditions = [], array $likeConditions = []): array**
   - Retrieves records from a table with optional exact match and `LIKE` conditions.
   - `$conditions`: Key-value pairs for exact matches (e.g., `['id' => 1]`).
   - `$likeConditions`: Key-value pairs for `LIKE` queries (e.g., `['name' => 'Doe%']`).
   - Returns an array of matching records.
   - **Example**:
     ```php
     $users = $db->find('users', ['status' => 'active'], ['name' => 'Doe%']);
     // Returns users with status="active" and name starting with "Doe"
     ```

3. **create(string $table, array $data): int**
   - Inserts a new record into a table.
   - Returns the ID of the created record.
   - **Example**:
     ```php
     $id = $db->create('users', ['name' => 'John Doe', 'status' => 'active']);
     // Returns: 3 (new record ID)
     ```

4. **update(string $table, array $data, array $conditions): bool**
   - Updates records in a table based on conditions.
   - Returns `true` on success, `false` on failure.
   - **Example**:
     ```php
     $success = $db->update('users', ['status' => 'inactive'], ['id' => 1]);
     // Returns: true
     ```

5. **delete(string $table, array $conditions): bool**
   - Deletes records from a table based on conditions.
   - Returns `true` on success, `false` on failure.
   - **Example**:
     ```php
     $success = $db->delete('users', ['id' => 1]);
     // Returns: true
     ```

### Notes
- All methods use prepared statements to prevent SQL injection.
- The `find` method supports `LIKE` queries with `%` wildcards for partial matching.
- Ensure the database schema matches the expected table structure (see `database.sql`).

## Testing the Framework
1. **Set Up a Test Environment**:
   - Deploy the framework to a web server with PHP and MySQL.
   - Ensure `config/database.php` has valid credentials.

2. **Test CRUD Endpoints**:
   - Use a tool like Postman or cURL:
     - `GET /api/v1/users`: List all users.
     - `GET /api/v1/users/?name=Doe%`: List users with names starting with "Doe".
     - `POST /api/v1/users` with body `{"name": "Test User", "status": "active"}`.
     - `PUT /api/v1/users?id=1` with body `{"status": "inactive"}`.
     - `DELETE /api/v1/users?id=1`.

3. **Test Custom API**:
   - `GET /api/v1/users/getStatus/1`: Check the status response.

4. **Test HTML View**:
   - Open `http://yourdomain.com/users/status/1` in a browser to view the HTML page.

5. **Check Error Logs**:
   - Monitor PHP and Apache logs for issues:
     ```bash
     tail -f /var/log/apache2/error.log
     ```

## Directory Structure
```
├── .htaccess              # Root rewrite to public/
├── config/
│   └── database.php       # Database configuration
├── public/
│   ├── .htaccess          # Routes requests to index.php
│   └── index.php          # Framework entry point
├── src/
│   ├── Database.php       # Database abstraction layer
│   ├── RestApi.php        # API handler
│   ├── Router.php         # Route matching
│   └── Controllers/
│       └── UsersController.php  # Example controller
├── views/
│   └── status.php         # Example HTML view
├── composer.json          # Composer configuration (optional)
└── database.sql           # Database schema
```

## Troubleshooting
- **Database Connection Errors**:
  - Verify `config/database.php` credentials.
  - Test connectivity with a script:
    ```php
    <?php
    $config = require __DIR__ . '/config/database.php';
    try {
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['user'], $config['pass']);
        echo "Connected!";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    ```

- **404 Errors**:
  - Ensure `.htaccess` files are present and `mod_rewrite` is enabled.
  - Check route definitions in `public/index.php`.

- **File Not Found**:
  - Verify file paths and permissions (e.g., `chmod 644 src/*.php`).
  - Ensure `index.php` is in `public/` and not misnamed (e.g., `indexd.php`).

- **LIKE Query Issues**:
  - Ensure the `%` wildcard is included in the query parameter (e.g., `name=Doe%`).
  - Check the database schema for the correct column names.

For further assistance, check the error logs or contact the developer.