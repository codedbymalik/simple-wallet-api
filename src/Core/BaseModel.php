<?php

namespace Core;

use PDO;
use PDOStatement;

abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Find a single record by ID
     */
    public function find(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch() ?: null;
    }

    /**
     * Find all records
     */
    public function all(): array
    {
        $query = "SELECT * FROM {$this->table}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $where = implode(' AND ', $conditions);
        $query = "SELECT * FROM {$this->table} WHERE $where";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Find one record by criteria
     */
    public function findOneBy(array $criteria): ?array
    {
        $results = $this->findBy($criteria);
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Create a new record
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($key) => ":$key", array_keys($data)));
        
        $query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): int
    {
        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $sets[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $params[':id'] = $id;
        $where = implode(', ', $sets);
        $query = "UPDATE {$this->table} SET $where WHERE id = :id";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): int
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount();
    }

    /**
     * Get PDO instance for advanced queries
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
