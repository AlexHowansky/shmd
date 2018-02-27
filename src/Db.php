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

    use Configurable;

    /**
     * Database connector.
     *
     * @var \SQLite3
     */
    protected $db = null;

    /**
     * Constructor.
     *
     * @param Config $config The configuration.
     */
    public function __construct(Config $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }
        $exists = file_exists($this->config['database']);
        $this->db = new \SQLite3($this->config['database']);
        if ($exists === false) {
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS faces (
                    id TEXT NOT NULL PRIMARY KEY,
                    name TEXT NOT NULL,
                    gender TEXT,
                    class INT,
                    photo TEXT
                );'
            );
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS photos (
                    name TEXT NOT NULL,
                    gallery TEXT NOT NULL,
                    photo TEXT NOT NULL
                );'
            );
            $this->db->exec('CREATE UNIQUE INDEX IF NOT EXISTS photos_unique ON photos (name, gallery, photo);');
        }
    }

    /**
     * Find the person associated with a FaceID.
     *
     * @param string $id The FaceID.
     *
     * @return array The record if found.
     */
    public function findFace(string $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM faces WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: [];
    }

    /**
     * Search for the photos a name appears in.
     *
     * @param string $name  The name to search for.
     * @param int    $limit Limit the search to this many records.
     *
     * @return array The photos the name appears in.
     */
    public function search(string $name, int $limit = 20): array
    {
        $photos = [];
        $stmt = $this->db->prepare('SELECT DISTINCT gallery, photo FROM photos WHERE name like :name LIMIT :limit');
        $stmt->bindValue(':name', '%' . $name . '%');
        $stmt->bindValue(':limit', $limit);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $photos[] = $row;
        }
        if (empty($this->config['searchLog']) === false) {
            file_put_contents(
                $this->config['searchLog'],
                json_encode([
                    'timestamp' => time(),
                    'searchTerm' => $name,
                    'results' => $photos,
                ]) . "\n",
                FILE_APPEND
            );
        }
        return $photos;
    }

    /**
     * Write a row to a table.
     *
     * @param string $table The table to write to.
     * @param array  $row   The data to write.
     *
     * @return Db Allow method chaining.
     *
     * @throws \RuntimeException On error.
     */
    public function write(string $table, array $row): Db
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
            'INSERT OR IGNORE INTO ' . $table . ' (' .
            join(', ', array_keys($row)) . ') VALUES (:' .
            join(', :', array_keys($row)) . ');'
        );
        foreach ($row as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        if ($stmt->execute() === false) {
            throw new \RuntimeException('Database error: ' . $this->db->lastErrorMsg());
        }
        return $this;
    }

}
