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
     * @var \PDO
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
        $this->db = new \PDO('sqlite:' . $this->config['database']);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if ($exists === false) {
            $this->db->exec(
                'CREATE TABLE faces (
                    id TEXT NOT NULL PRIMARY KEY,
                    name TEXT NOT NULL,
                    class TEXT NOT NULL,
                    external_id TEXT NOT NULL,
                    metadata TEXT NOT NULL
                );'
            );
            $this->db->exec(
                'CREATE TABLE photos (
                    face_id TEXT NOT NULL,
                    gallery TEXT NOT NULL,
                    photo TEXT NOT NULL
                );'
            );
            $this->db->exec('CREATE UNIQUE INDEX photos_unique ON photos (face_id, gallery, photo);');
            $this->db->exec('CREATE INDEX photos_photo ON photos (gallery, photo);');
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
     * Get a list of people in a given photo.
     *
     * @param string $gallery The gallery the photo is in.
     * @param string $photo   The photo to get the list of people in.
     *
     * @return array The people in the photo.
     */
    public function getPeopleInPhoto(string $gallery, string $photo)
    {
        $stmt = $this->db->prepare(
            'SELECT faces.id, faces.name, faces.gender, faces.class FROM faces ' .
            'JOIN photos ON photos.face_id = faces.id ' .
            'WHERE photos.gallery = :gallery and photos.photo = :photo ' .
            'ORDER BY faces.name'
        );
        $stmt->bindValue(':gallery', $gallery);
        $stmt->bindValue(':photo', $photo);
        $result = $stmt->execute();
        $people = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $people[] = $row;
        }
        return $people;
    }

    /**
     * Search for the photos a name appears in.
     *
     * @param string $string The id or name to search for.
     * @param int    $limit  Limit the search to this many records.
     *
     * @return array The photos the person appears in.
     */
    public function search(string $string, int $limit = 20): array
    {
        $photos = [];
        if (preg_match('/^[0-9a-f-]{36}$/', $string) === 1) {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT photos.gallery, photos.photo FROM photos ' .
                'JOIN faces ON faces.id = photos.face_id ' .
                'WHERE faces.id = :id LIMIT :limit'
            );
            $stmt->bindValue(':id', $string);
            $stmt->bindValue(':limit', $limit);
        } else {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT photos.gallery, photos.photo FROM photos ' .
                'JOIN faces ON faces.id = photos.face_id ' .
                'WHERE faces.name like :name LIMIT :limit'
            );
            $stmt->bindValue(':name', '%' . $string . '%');
            $stmt->bindValue(':limit', $limit);
        }
        $result = $stmt->execute();
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $photos[] = $row;
        }
        if (empty($this->config['searchLog']) === false) {
            file_put_contents(
                $this->config['searchLog'],
                json_encode([
                    'timestamp' => time(),
                    'searchTerm' => $string,
                    'results' => $photos,
                ]) . "\n",
                FILE_APPEND
            );
        }
        return $photos;
    }

    protected function write(string $sql, array $row): bool
    {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($row);
        } catch (\PDOException $e) {
            if ($stmt->errorCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function writeFace(array $row): bool
    {
        return $this->write(
            'INSERT INTO faces (id, name, class, external_id, metadata) VALUES (:id, :name, :class, :external_id, :metadata)',
            $row
        );
    }

    public function writePhoto(array $row): bool
    {
        return $this->write(
            'INSERT INTO photos (face_id, gallery, photo) VALUES (:face_id, :gallery, :photo)',
            $row
        );
    }

}
