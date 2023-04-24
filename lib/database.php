<?php
require_once 'functions.php';

class mysqlii extends mysqli {
    protected $counter = 0;

    /**
     * Execute a query, increase the counter, and log;
     * @return mysqli_result|false
     */
    #[\ReturnTypeWillChange]
    public function query(
        /*string*/ $sql,
        /*mixed*/ ...$values
    ) /*: mysqli_result|bool*/ {
        if(count($values)) $sql = sprintf($sql, ...$values);
        // site_log('SQL %d: %s', ++$this->counter, $sql);
        try {
            return parent::query($sql);
        }
        catch (mysqli_sql_exception $e) {
            $errno = $e->getCode();
            site_log(
                "Database Error %d: %s\n%s",
                $e->getCode(), $e->getMessage(), $sql
            );
        }
        return false;
    }

    /**
     * Get all result rows as a two-dimensional array.
     * @return array|false
     */
    public function get_all(
        string $sql,
        /*mixed*/ ...$values
    ) {
        $result = $this->query($sql, ...$values);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : false;
    }

    /**
     * Get all values of the first column of each rows of the query result.
     * @return array|false
     */
    public function get_col(
        string $sql,
        /*mixed*/ ...$values
    ) {
        $result = $this->query($sql, ...$values);
        if(!$result) return false;
        $ret = array();
        // while($v = $result->fetch_column()) $ret[] = $v; // PHP 8.1 or later
        while($row = $result->fetch_row()) $ret[] = $row[0];
        return $ret;
    }

    /**
     * Get the value of the first column of the first row of the query result.
     * @return null|int|float|string|false
     */
    public function get_one(
        string $sql,
        /*mixed*/ ...$values
    ) {
        $result = $this->query($sql, ...$values);
        // return $result ? $result->fetch_column() : false; // PHP 8.1 or later
        if(!$result) return false;
        $row = $result->fetch_row();
        return $row[0];
    }

    /**
     * Get the first row as an associative array.
     * @return array|null|false
     */
    public function get_row(
        string $sql,
        /*mixed*/ ...$values
    ) {
        $result = $this->query($sql, ...$values);
        return $result ? $result->fetch_assoc() : false;
    }

    /**
     * Insert the associative array $data as a new row into $table_name and returns the inserted ID.
     * @return int|string|false
     */
    public function insert(
        string $table_name,
        array $data,
        bool $test = false
    ) {
        $sql = sprintf(
            'INSERT INTO `%s` SET %s',
            self::escape($table_name),
            self::join_assoc($data, ',')
        );
        if($test) return $sql;
        return $this->query($sql) ? $this->insert_id : false;
    }

    /**
     * Insert the list array $data as new rows into $table_name and returns the number of affected rows.
     * @return int|string|false
     */
    public function insert_multi(
        string $table_name,
        array $data,
        bool $test = false
    ) {
        if(!array_is_list($data)) $data = [$data];
        $keys = [];
        foreach($data as $row)
            $keys = array_merge($keys, array_diff(array_keys($row), $keys));

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES' . chr(10),
            self::escape($table_name),
            implode('`, `', $keys)
        );

        $rows = [];
        foreach($data as $row) {
            $cols = [];
            foreach($keys as $key) {
                $cols[] = isset($row[$key])
                ? (chr(0x27) . self::escape($row[$key]) . chr(0x27))
                : 'NULL';
            }
            $rows[] = '(' . implode(', ', $cols) . ')';
        }
        $sql .= implode(",\n", $rows);

        if($test) return $sql;
        return $this->query($sql) ? $this->affected_rows : false;
    }

    /**
     * Get all rows which meets $conditions from $table_name.
     * @return array|string|false
     */
    public function select_all(
        string $table_name,
        array $conditions = [],
        bool $test = false
    ) {
        $sql = sprintf(
            'SELECT * FROM `%s`',
            self::escape($table_name)
        );
        if(count($conditions))
            $sql .= ' WHERE ' . self::join_assoc($conditions);

        if($test) return $sql;
        return $this->get_all($sql);
    }

    /**
     * Get the first row which meets $conditions from $table_name.
     * @return array|string|null|false
     */
    public function select_row(
        string $table_name,
        array $conditions,
        bool $test = false
    ) {
        $sql = sprintf(
            'SELECT * FROM `%s` WHERE %s LIMIT 1',
            self::escape($table_name),
            self::join_assoc($conditions)
        );
        if($test) return $sql;
        return $this->get_row($sql);
    }

    /**
     * Delete at most one row which meets $conditions in $table_name.
     * @return bool
     */
    public function delete(
        string $table_name,
        array $conditions,
        bool $test = false
    ) {
        $sql = sprintf(
            'DELETE FROM `%s` WHERE %s',
            self::escape($table_name),
            self::join_assoc($conditions)
        );
        return $test ? $sql : $this->query($sql);
    }

    /**
     * Update at most one row which meets $conditions into $data in $table_name.
     * @return bool
     */
    public function update(
        string $table_name,
        array $data,
        array $conditions,
        bool $test = false
    ) {
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::escape($table_name),
            self::join_assoc($data, ','),
            self::join_assoc($conditions)
        );
        return $test ? $sql : $this->query($sql);
    }

    /**
     * Try to insert $data as a new row into $table_name, or update if having corresponding unique keys.
     * NOTE: This is NOT the actual REPLACE syntax, which first deletes the row if conflicted.
     * @return int|string|false
     */
    public function replace(
        string $table_name,
        array $data,
        bool $test = false
    ) {
        $kv_pairs = self::join_assoc($data, ',');
        $sql = sprintf(
            'INSERT INTO `%s` SET %s ON DUPLICATE KEY UPDATE %s',
            self::escape($table_name),
            $kv_pairs,
            $kv_pairs
        );
        if($test) return $sql;
        return $this->query($sql) ? $this->insert_id : false;
    }


    /**
     * Escape single quote by prepending a backslash.
     * @return string
     */
    static public function escape(
        /*mixed*/ $str
    ) {
        return strtr($str, [
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "\\" => "\\\\",
            "\'" => "\\'",
            "\"" => "\\\"",
            "\x1a" => "\\x1a"
        ]);
    }

    /**
     * Convert an associative array into formatted strings and then concatenate them by $glue.
     * @return string
     */
    static public function join_assoc(
        array $assoc,
        string $glue = ' AND '
    ) {
        $pieces = [];
        foreach($assoc as $key => $value) $pieces[] = sprintf("`%s` = '%s'", self::escape($key), self::escape($value));
        return implode($glue, $pieces);
    }
}
