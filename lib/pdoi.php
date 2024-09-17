<?php

class PDOi extends PDO {

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ) {
        $options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ], $options ?? []);
        parent::__construct($dsn, $username, $password, $options);
    }


    /**
     * Execute a query, increase the counter, and log.
     */
    public function query(
        string $query,
        ?int $fetchMode = PDO::FETCH_ASSOC,
        mixed ...$fetchModeArgs
    ) : PDOStatement|false {
        try {
            return parent::query($query);
        }
        catch (PDOException $e) {
            $errno = $e->getCode();
            site_log(
                "Database Error %d: %s\n%s",
                $e->getCode(), $e->getMessage(), $query
            );
        }
        return false;
    }

    /**
     * Get all result rows as a two-dimensional array.
     */
    public function get_all(
        string $sql,
        mixed ...$values
    ) : array|false {
        if (count($values)) $sql = sprintf($sql, ...$values);
        $stmt = $this->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }

    /**
     * Get all values of the first column of each rows of the query result.
     */
    public function get_col(
        string $sql,
        mixed ...$values
    ) : array|false {
        if (count($values)) $sql = sprintf($sql, ...$values);
        $stmt = $this->query($sql);
        if (! $stmt) return false;
        $ret = array();
        while($v = $stmt->fetchColumn()) $ret[] = $v;
        return $ret;
    }

    /**
     * Get the value of the first column of the first row of the query result.
     */
    public function get_one(
        string $sql,
        mixed ...$values
    ) : mixed {
        if (count($values)) $sql = sprintf($sql, ...$values);
        $stmt = $this->query($sql);
        return $stmt ? $stmt->fetchColumn() : false;
    }

    /**
     * Get the first row as an associative array.
     */
    public function get_row(
        string $sql,
        mixed ...$values
    ) : array|false {
        if (count($values)) $sql = sprintf($sql, ...$values);
        $stmt = $this->query($sql);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    /**
     * Insert the associative array $data as a new row into $table_name and returns the inserted ID.
     */
    public function insert(
        string $table_name,
        array $data
    ) : string|false {
        if (! static::validate($table_name)) return false;

        $sql = $this->join_columns(array_keys($data), ', ');
        if (! $sql) return false;
        $sql = "INSERT INTO $table_name SET $sql";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($data) ? $this->lastInsertId : false;
    }

    /**
     * Get all rows which meets $conditions from $table_name.
     */
    public function select_all(
        string $table_name,
        array $conditions = []
    ) : array|false {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "SELECT * FROM $table_name WHERE $sql";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        if (! $stmt->execute($conditions)) return false;
        return $stmt->fetchAll();
    }

    /**
     * Get the first row which meets $conditions from $table_name.
     * @return array|string|null|false
     */
    public function select_row(
        string $table_name,
        array $conditions
    ) {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "SELECT * FROM $table_name WHERE $sql LIMIT 1";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        if (! $stmt->execute($conditions)) return false;
        return $stmt->fetch();
    }

    /**
     * Delete at most one row which meets $conditions in $table_name.
     * @return bool
     */
    public function delete(
        string $table_name,
        array $conditions,
        ?int $limit = 1
    ) : int|false {
        if (! static::validate($table_name)) return false;
        $sql = count($conditions) ? $this->join_columns(array_keys($conditions)) : '1';
        if (! $sql) return false;
        $sql = "DELETE FROM $table_name WHERE $sql";
        if ($limit > 0) $sql .= " LIMIT $limit";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        if (! $stmt->execute($conditions)) return false;
        return $this->rowCount;
    }

    /**
     * Try to insert $data as a new row into $table_name, or update if having corresponding unique keys.
     * NOTE: This is NOT the actual REPLACE syntax, which first deletes the row if conflicted.
     */
    public function replace(
        string $table_name,
        array $data
    ) : string|false {
        if (! static::validate($table_name)) return false;

        $sql = $this->join_columns(array_keys($data), ', ');
        if (! $sql) return false;
        $sql = "INSERT INTO $table_name SET $sql ON DUPLICATE KEY UPDATE $sql";

        $stmt = parent::prepare($sql);
        if (! $stmt) return false;
        return $stmt->execute($data) ? $this->lastInsertId : false;
    }

    static public function validate(
        string $str
    ) : bool {
        return preg_match('/^[A-Za-z_]\w*+$/', $str) ? true : false;
    }

    static public function join_columns(
        array $columns,
        string $glue = ' AND '
    ) : string|false {
        $pieces = [];
        foreach ($columns as $col) {
            if (! static::validate($col)) return false;
            $pieces[] = "$col = :$col";
        }
        return implode($glue, $pieces);
    }
}
