<?php

namespace Framework\Controllers;

use Framework\Database;

class UsersController {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getStatus($param) {
        $results = $this->db->query("SELECT status FROM users WHERE id = :id", ['id' => $param]);
        $result = $results[0] ?? []; // Get the first row, or empty array if none
        return ['status' => $result['status'] ?? 'unknown', 'param' => $param];
    }

    public function showStatusPage($param) {
        $results = $this->db->query("SELECT status FROM users WHERE id = :id", ['id' => $param]);
        $result = $results[0] ?? []; // Get the first row, or empty array if none
        $status = $result['status'] ?? 'unknown';

        ob_start();
        require __DIR__ . '/../../views/status.php';
        return ob_get_clean();
    }
}