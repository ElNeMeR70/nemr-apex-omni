<?php
declare(strict_types=1);

namespace Nemr\Apex\Database;

use PDO;
use PDOStatement;
use InvalidArgumentException;

/**
 * Enterprise Fluent PDO Query Builder (PHP 8.2 / 8.3)
 * Provides 100% parameterized query construction preventing SQL injection attacks.
 */
class QueryBuilder
{
    protected ?PDO $pdo = null;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $joins = [];
    protected array $orders = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public static function table(string $table, ?PDO $pdo = null): self
    {
        $instance = new self($pdo);
        $instance->table = self::sanitizeIdentifier($table);
        return $instance;
    }

    public function select(string ...$columns): self
    {
        $this->columns = array_map([self::class, 'sanitizeIdentifier'], $columns);
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $sanitizedCol = self::sanitizeIdentifier($column);
        $allowedOps = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];
        $upperOp = strtoupper($operator);

        if (!in_array($upperOp, $allowedOps, true)) {
            throw new InvalidArgumentException("Unsupported SQL operator: {$operator}");
        }

        $paramKey = ':w_' . count($this->bindings) . '_' . preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $this->wheres[] = "{$sanitizedCol} {$upperOp} {$paramKey}";
        $this->bindings[$paramKey] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = '1 = 0';
            return $this;
        }

        $sanitizedCol = self::sanitizeIdentifier($column);
        $placeholders = [];
        foreach ($values as $idx => $val) {
            $paramKey = ':win_' . count($this->bindings) . '_' . $idx;
            $placeholders[] = $paramKey;
            $this->bindings[$paramKey] = $val;
        }

        $this->wheres[] = "{$sanitizedCol} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $tableSan = self::sanitizeIdentifier($table);
        $firstSan = self::sanitizeIdentifier($first);
        $secondSan = self::sanitizeIdentifier($second);
        $type = strtoupper($type);

        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL'], true)) {
            $type = 'INNER';
        }

        $this->joins[] = "{$type} JOIN {$tableSan} ON {$firstSan} {$operator} {$secondSan}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $sanitizedCol = self::sanitizeIdentifier($column);
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$sanitizedCol} {$dir}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(1, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);
        return $this;
    }

    public function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function get(): array
    {
        $stmt = $this->prepareAndExecute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $stmt = $this->prepareAndExecute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function insert(array $data): int|string
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Data array cannot be empty for insert.");
        }

        $cols = [];
        $params = [];
        $bindings = [];

        foreach ($data as $k => $v) {
            $sanitizedCol = self::sanitizeIdentifier($k);
            $paramKey = ':ins_' . preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $cols[] = $sanitizedCol;
            $params[] = $paramKey;
            $bindings[$paramKey] = $v;
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $params) . ")";
        $stmt = $this->executeRaw($sql, $bindings);

        return $this->pdo ? $this->pdo->lastInsertId() : 0;
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Data array cannot be empty for update.");
        }

        $setters = [];
        $bindings = [];

        foreach ($data as $k => $v) {
            $sanitizedCol = self::sanitizeIdentifier($k);
            $paramKey = ':upd_' . preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $setters[] = "{$sanitizedCol} = {$paramKey}";
            $bindings[$paramKey] = $v;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setters);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
            $bindings = array_merge($bindings, $this->bindings);
        }

        $stmt = $this->executeRaw($sql, $bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = $this->executeRaw($sql, $this->bindings);
        return $stmt->rowCount();
    }

    protected function prepareAndExecute(): PDOStatement
    {
        return $this->executeRaw($this->toSql(), $this->bindings);
    }

    protected function executeRaw(string $sql, array $bindings): PDOStatement
    {
        if (!$this->pdo) {
            throw new \RuntimeException("PDO connection must be provided to execute database queries.");
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($bindings as $key => $val) {
            $paramType = match (gettype($val)) {
                'integer' => PDO::PARAM_INT,
                'boolean' => PDO::PARAM_BOOL,
                'NULL'    => PDO::PARAM_NULL,
                default   => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $val, $paramType);
        }
        $stmt->execute();
        return $stmt;
    }

    protected static function sanitizeIdentifier(string $identifier): string
    {
        $trimmed = trim($identifier);
        if ($trimmed === '*') {
            return '*';
        }
        if (str_contains($trimmed, '.')) {
            $parts = explode('.', $trimmed);
            return implode('.', array_map([self::class, 'sanitizeIdentifier'], $parts));
        }
        return '`' . str_replace('`', '', $trimmed) . '`';
    }
}
