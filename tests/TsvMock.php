<?php
/**
 * TSV-based database mock for testing DB-dependent functions.
 *
 * Provides TsvResultSet (mimics mysqli_result) and helper functions
 * that override DBQuery/db_fetch_row so tests can supply tabular data
 * without a real MySQL connection.
 *
 * Usage in tests:
 *   $rs = TsvResultSet::fromTsv("COL1\tCOL2\nval1\tval2\n");
 *   $rows = DBGet($rs);
 */

class TsvResultSet
{
    private array $rows = [];
    private int $pos = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Build from a TSV string. First line is headers, subsequent lines are data.
     */
    public static function fromTsv(string $tsv): self
    {
        $lines = array_filter(explode("\n", trim($tsv)), fn($l) => $l !== '');
        if (count($lines) < 1) return new self([]);

        $headers = explode("\t", array_shift($lines));
        $rows = [];
        foreach ($lines as $line) {
            $values = explode("\t", $line);
            $row = [];
            foreach ($headers as $i => $h) {
                $row[strtoupper(trim($h))] = $values[$i] ?? '';
            }
            $rows[] = $row;
        }
        return new self($rows);
    }

    /**
     * Build from an array of associative arrays (keys are column names).
     */
    public static function fromArray(array $data): self
    {
        $rows = [];
        foreach ($data as $row) {
            $upper = [];
            foreach ($row as $k => $v) {
                $upper[strtoupper($k)] = $v;
            }
            $rows[] = $upper;
        }
        return new self($rows);
    }

    /**
     * Returns an empty result set.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Mimics mysqli_result::fetch_assoc() — returns next row or null.
     */
    public function fetchRow(): ?array
    {
        if ($this->pos >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->pos++];
    }

    public function reset(): void
    {
        $this->pos = 0;
    }

    public function count(): int
    {
        return count($this->rows);
    }
}

/**
 * Override db_fetch_row to work with TsvResultSet in test context.
 * This MUST be loaded BEFORE DatabaseInc.php so it takes precedence.
 */
if (!function_exists('db_fetch_row')) {
    function db_fetch_row($result)
    {
        if ($result instanceof TsvResultSet) {
            return $result->fetchRow();
        }
        return null;
    }
}

/**
 * Stub DBQuery for tests — returns whatever is passed (expects TsvResultSet).
 */
if (!function_exists('DBQuery')) {
    function DBQuery($sql)
    {
        // In mock mode, the caller should pass a TsvResultSet directly to DBGet
        return $sql;
    }
}

/**
 * Stub db_start for tests.
 */
if (!function_exists('db_start')) {
    function db_start()
    {
        return true;
    }
}
