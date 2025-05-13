<?php

namespace Framework;

class Database {
    private $pdo;

    public function __construct(array $config) {
        // Validate configuration
        if (empty($config['host']) || empty($config['dbname']) || !isset($config['user']) || !isset($config['pass'])) {
            throw new \InvalidArgumentException('Database configuration is incomplete. Ensure host, dbname, user, and pass are provided.');
        }

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        try {
            $this->pdo = new \PDO($dsn, $config['user'], $config['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            throw new \PDOException("Failed to connect to database: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(string $table, array $conditions = [], array $likeConditions = []): array {
        $whereClauses = [];
        $params = [];

        // Handle exact match conditions
        if ($conditions) {
            $whereClauses = array_map(fn($k) => "$k = :$k", array_keys($conditions));
            $params = array_merge($params, $conditions);
        }

        // Handle LIKE conditions
        if ($likeConditions) {
            foreach ($likeConditions as $key => $value) {
                $paramKey = "like_$key"; // Unique parameter key to avoid conflicts
                $whereClauses[] = "$key LIKE :$paramKey";
                $params[$paramKey] = $value;
            }
        }

        $where = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';
        $sql = "SELECT * FROM $table" . $where;
        return $this->query($sql, $params);
    }

    public function create(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $conditions): bool {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $where = implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($conditions)));
        $sql = "UPDATE $table SET $set WHERE $where";
        $params = array_merge($data, $conditions);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(string $table, array $conditions): bool {
        $where = implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($conditions)));
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($conditions);
    }
}