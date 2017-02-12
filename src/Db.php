<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2017 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

/**
 * Database interface.
 */
class Db
{

    /**
     * Database connector.
     *
     * @var \SQLite3
     */
    protected $db = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $file = realpath(__DIR__ . '/..') . '/shmd.sq3';
        $exists = file_exists($file);
        $this->db = new \SQLite3(realpath(__DIR__ . '/..') . '/shmd.sq3');
        if ($exists === false) {
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS faces (
                    id TEXT NOT NULL PRIMARY KEY,
                    name TEXT NOT NULL,
                    gender TEXT,
                    class INT,
                    photo TEXT
                );
            ');
        }
    }

    /**
     * Write a row to a table.
     *
     * @param string $table The table to write to.
     * @param array  $row   The data to write.
     *
     * @return \Shmd\Db Allow method chaining.
     *
     * @throws \RuntimeException On error.
     */
    public function write(string $table, array $row): \Shmd\Db
    {
        if (preg_match('/^[a-zA-z][a-zA-Z0-9]+$/', $table) !== 1) {
            throw new \RuntimeException('Bad table name.');
        }
        foreach (array_keys($row) as $key) {
            if (preg_match('/^[a-zA-z][a-zA-Z0-9]+$/', $key) !== 1) {
                throw new \RuntimeException('Bad field name.');
            }
        }
        $stmt = $this->db->prepare(
            'INSERT INTO ' . $table . ' (' . join(', ', array_keys($row)) . ') VALUES (:' . join(', :', array_keys($row)) . ');'
        );
        foreach ($row as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        if ($stmt->execute() === false) {
            throw new \RuntimeException('Database write failed.');
        }
        return $this;
    }

}
