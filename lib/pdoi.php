<?php

class PDOi extends PDO {
    public static $defaultOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    /**
     * Constructor with different parameter list from parent.
     * The frist parameter of parent constructor is here explode into two parts:
     * * $driver: driver name (ex: mysql, pgsql, sqlsrv, sqlite)
     * * $dsn_kv: string for sqlite, assoc array for other drivers
     */
    public function __construct(
        string $driver,
        /*array|string*/ $dsn_kv,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ) {
        $dsn = $driver . ':';
        if (is_string($dsn_kv)) $dsn .= $dsn_kv;
        else {
            $pieces = [];
            foreach ($dsn_kv as $k => $v) $pieces[] = "$k=$v";
            $dsn .= implode(';', $pieces);
        }
        $options = array_merge(self::$defaultOptions, $options ?? []);
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Execute a query, with or without paremeters or values.
     *
     * @example
     * query('SELECT 1 + 2');
     *
     * @example
     * query('SELECT ? + ?', [1, 2]);
     *
     * @example
     * query('SELECT :a + :b', ['a' => 1, 'b' => 2]);
     *
     * @example
     * query('SELECT %d + %d', 1, 2);
     *
     */
    #[\ReturnTypeWillChange]
    public function query(
        string $query,
        mixed ...$values
    ) /*: PDOStatement|false*/ {
        try {
            if (! count($values)) {
                $stmt = parent::query($query);
            }
            else if (is_array($values[0])) {
                $stmt = parent::prepare($query);
                $stmt->execute($values[0]);
            }
            else {
                $query = sprintf($query, ...$values);
                $stmt = parent::query($query);
            }
            return $stmt;
        }
        catch (PDOException $e) {
            if (function_exists('site_log')) {
                site_log(
                    "Database Error %d: %s\n%s",
                    $e->getCode(), $e->getMessage(), $query
                );
                if (count($values) && is_array($values[0])) site_log($values[0]);
            }
        }
        return false;
    }

    /**
     * Get all result rows as a two-dimensional array.
     */
    public function get_all(
        string $sql,
        /*mixed*/ ...$values
    ) /*: array|false*/ {
        $stmt = $this->query($sql, ...$values);
        return $stmt ? $stmt->fetchAll() : false;
    }

    /**
     * Get all values of the first column of each rows.
     */
    public function get_col(
        string $sql,
        /*mixed*/ ...$values
    ) /*: array|false*/ {
        $stmt = $this->query($sql, ...$values);
        if (! $stmt) return false;
        $ret = array();
        while ($v = $stmt->fetchColumn()) $ret[] = $v;
        return $ret;
    }

    /**
     * Get the value of the first column of the first row.
     */
    public function get_one(
        string $sql,
        /*mixed*/ ...$values
    ) /*: mixed*/ {
        $stmt = $this->query($sql, ...$values);
        return $stmt ? $stmt->fetchColumn() : false;
    }

    /**
     * Get the first row.
     */
    public function get_row(
        string $sql,
        /*mixed*/ ...$values
    ) /*: array|false*/ {
        $stmt = $this->query($sql, ...$values);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Insert the associative array $data as a new row into $table_name and returns the inserted ID.
     * NOTE: Return type is always string, even with ATTR_STRINGIFY_FETCHES set to false and the column has type integer.
     *       If id column is not AUTO_INCREMENT, the return value is string '0', which evaluates to false.
     */
    public function insert(
        string $table_name,
        array $data
    ) /*: string|false*/ {
        if (! static::validate($table_name)) return false;
        $sql = $this->join_columns(array_keys($data), ', ');
        if (! $sql) return false;
        $sql = "INSERT INTO $table_name SET $sql";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($data) ? parent::lastInsertId() : false;
    }

    /**
     * Insert multiple rows from a 2d array, which is a list with assoc array as elements.
     */
    public function insert_multi(
        string $table_name,
        array $rows
    ) /*: int|false*/ {
        if (! static::validate($table_name)) return false;
        $cols = [];
        foreach ($rows as $row)
            $cols = array_merge($cols, array_diff(array_keys($row), $cols));
        foreach ($cols as $col) if (! static::validate($col)) return false;

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES ',
            $table_name,
            implode(', ', $cols)
        );
        $placeholder = '(?' . str_repeat(', ?', count($cols) - 1) . ')';
        $sql .= str_repeat("\n$placeholder,", count($rows) - 1) . "\n$placeholder";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;

        $i = 1;
        foreach ($rows as $row) {
            foreach ($cols as $col) {
                if (isset($row[$col])) $stmt->bindValue($i++, $row[$col]);
                else $stmt->bindValue($i++, null, PDO::PARAM_NULL);
            }
        }
        return $stmt->execute() ? $stmt->rowCount() : false;
    }

    /**
     * Get all rows which meets $conditions from $table_name.
     */
    public function select_all(
        string $table_name,
        array $conditions = []
    ) /*: array|false*/ {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "SELECT * FROM $table_name WHERE $sql";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($conditions) ? $stmt->fetchAll() : false;
    }

    /**
     * Get the first row which meets $conditions from $table_name.
     */
    public function select_row(
        string $table_name,
        array $conditions
    ) /*: array|false*/ {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "SELECT * FROM $table_name WHERE $sql LIMIT 1";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($conditions) ? $stmt->fetch() : false;
    }

    /**
     * Delete rows which meets $conditions in $table_name.
     */
    public function delete(
        string $table_name,
        array $conditions,
        ?int $limit = 1
    ) /*: int|false*/ {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "DELETE FROM $table_name WHERE $sql";
        if ($limit > 0) $sql .= " LIMIT $limit";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($conditions) ? $stmt->rowCount() : false;
    }

    /**
     * Update rows.
     * NOTE: $data and $conditions may have duplicate keys with different values.
     *       Therefore value binding would be different here.
     */
    public function update(
        string $table_name,
        array $data,
        array $conditions
    ) /*: int|false*/ {
        if (! static::validate($table_name)) return false;
        $sql = "UPDATE $table_name";

        $pieces = [];
        foreach ($data as $key => $v) {
            if (! static::validate($key)) return false;
            $pieces[] = "$key = ?";
        }
        $sql .= ' SET ' . implode(', ', $pieces);

        $pieces = [];
        foreach ($conditions as $key => $v) {
            if (! static::validate($key)) return false;
            $pieces[] = "$key = ?";
        }
        $sql .= ' WHERE ' . implode(' AND ', $pieces);

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        $params = array_merge(array_values($data), array_values($conditions));
        return $stmt->execute($params) ? $stmt->rowCount() : false;
    }

    public static function validate(
        string $str
    ) : bool {
        return preg_match('/^[A-Za-z_]\w*+$/', $str) ? true : false;
    }

    public static function join_columns(
        array $columns,
        string $glue = ' AND '
    ) /*: string|false*/ {
        $pieces = [];
        foreach ($columns as $col) {
            if (! static::validate($col)) return false;
            $pieces[] = "$col = :$col";
        }
        return implode($glue, $pieces);
    }
}
