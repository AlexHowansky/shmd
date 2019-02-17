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
 * AWS Rekognition interface.
 *
 * See http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-rekognition-2016-06-27.html
 */
class Rekog
{

    use Configurable;

    // The API version to use.
    const API_VERSION = '2016-06-27';

    // The faces metadata file name extension.
    const FACES_JSON = '.faces.json';

    // The names metadata file name extension.
    const NAMES_JSON = '.names.json';

    /**
     * @var \Aws\Rekognition\RekognitionClient
     *
     * An instance of the API client to use.
     */
    protected $api = null;

    /**
     * Does the collection exist?
     *
     * @return bool True if the collection exists.
     */
    protected function collectionExists(): bool
    {
        return in_array($this->config['aws']['collection'], $this->getApi()->listCollections()->get('CollectionIds'));
    }

    /**
     * Create the collection if one doesn't already exist.
     *
     * @return void
     */
    protected function createCollection(): void
    {
        if ($this->collectionExists() === false) {
            $this->getApi()->createCollection([
                'CollectionId' => $this->config['aws']['collection'],
            ]);
        }
    }

    /**
     * Get an API instance.
     *
     * @return \Aws\Rekognition\RekognitionClient
     */
    protected function getApi(): \Aws\Rekognition\RekognitionClient
    {
        if ($this->api === null) {
            $this->api = new \Aws\Rekognition\RekognitionClient([
                'credentials' => [
                    'key' => $this->config['aws']['key'],
                    'secret' => $this->config['aws']['secret'],
                ],
                'debug' => false,
                'region' => $this->config['aws']['region'],
                'version' => self::API_VERSION,
            ]);
        }
        return $this->api;
    }

    /**
     * Index a face.
     *
     * @param string $file       The file containing the face to index.
     * @param string $externalId The external ID to store in the Rekognition database.
     *
     * @return array The analysis data.
     */
    protected function indexFace(string $file, string $externalId): array
    {
        $result = $this->getApi()->indexFaces([
            'CollectionId' => $this->config['aws']['collection'],
            'DetectionAttributes' => ['ALL'],
            'ExternalImageId' => $externalId,
            'Image' => [
                'Bytes' => file_get_contents($file),
            ],
            'MaxFaces' => 1,
            'QualityFilter' => 'AUTO',
        ]);
        return $result->get('FaceRecords')[0];
    }

    /**
     * Identify people in photos in a given directory.
     *
     * Output of the detectFaces() function will be cached in a file
     * named <file>.faces.json. Output of the searchFacesByImage()
     * function will be cached in a file named <file>.names.json.
     *
     * Names of the people identified in the photo will be written
     * to the "photos" table in the database.
     *
     * @param string $directory The directory to scan for new photos.
     *
     * @return Rekog Allow method chaining.
     *
     * @throws \RuntimeException On error.
     */
    public function identify(string $directory): Rekog
    {

        if (is_dir($directory) === false) {
            throw new \RuntimeException('Unable to open directory.');
        }

        if ($this->collectionExists() === false) {
            throw new \RuntimeException('Unknown collection.');
        }

        $db = new Db($this->config);

        // Iterate over all the files in the named directory.
        foreach (new \DirectoryIterator($directory) as $file) {

            // Skip files that aren't photos.
            if (
                $file->isDot() === true ||
                $file->isFile() === false ||
                $file->getExtension() !== 'jpg'
            ) {
                continue;
            }

            Ansi::printf("{{white:%s}} ", $file->getFileName());

            // We'll save the identified people to this JSON file.
            $namesJsonFile = $file->getPathname() . self::NAMES_JSON;
            if (file_exists($namesJsonFile) === true) {
                Ansi::printf("{{yellow:%s}}\n", 'already recognized, skipping');
                continue;
            }

            // We'll save the detected faces information to this file.
            $facesJsonFile = $file->getPathname() . self::FACES_JSON;
            if (file_exists($facesJsonFile) === true) {
                $faces = json_decode(file_get_contents($facesJsonFile), true);
                Ansi::printf();
                echo '  already detected ' . count($faces) . " faces\n";
                exit;
            } else {
                echo '  detecting faces... ';
                $faces = $this->getApi()->detectFaces([
                    'Attributes' => ['ALL'],
                    'Image' => [
                        'Bytes' => file_get_contents($file->getPathname()),
                    ],
                ])->get('FaceDetails');
                echo 'found ' . count($faces) . "\n";
                file_put_contents($facesJsonFile, json_encode($faces));
            }

            // We use imagecreatefromstring() here so we can support
            // any file type without knowing what it is ahead of time.
            $gd = imagecreatefromstring(file_get_contents($file->getPathname()));
            $width = imagesx($gd);
            $height = imagesy($gd);

            $num = 0;
            $names = [];

            // Loop over each face detected in the file and create
            // a separate mini face JPG for it. This is then sent
            // to Rekognition for identification.
            foreach ($faces as $face) {

                // This file will contain only the detected face. It will
                // be a JPG file, but we'll not name it with that extension,
                // in order to avoid other processing scripts picking it up.
                $faceFile = sprintf(
                    '%s/%s_face_%02d',
                    $file->getPath(),
                    $file->getBasename('.' . $file->getExtension()),
                    ++$num
                );

                // Crop out the detected face and save it.
                if (file_exists($faceFile) === true) {
                    echo '    already created ' . $faceFile . "\n";
                } else {
                    $cropped = imagecrop(
                        $gd,
                        [
                            'x' => $face['BoundingBox']['Left'] * $width,
                            'y' => $face['BoundingBox']['Top'] * $height,
                            'width' => $face['BoundingBox']['Width'] * $width,
                            'height' => $face['BoundingBox']['Height'] * $height,
                        ]
                    );
                    imagejpeg($cropped, $faceFile);
                    echo '    face ' . $num . ' saved to ' . $faceFile . "\n";
                }

                // Look for matches for this face.
                try {
                    $matches = $this->getApi()->searchFacesByImage([
                        'CollectionId' => $this->config['aws']['collection'],
                        'MaxFaces' => 1,
                        'Image' => [
                            'Bytes' => file_get_contents($faceFile),
                        ],
                    ])->get('FaceMatches');
                    if (empty($matches) === true) {
                        echo "      face not found\n";
                    } else {
                        $match = array_shift($matches);
                        $row = $db->findFace($match['Face']['FaceId']);
                        if (empty($row) === true) {
                            echo "        not identified\n";
                        } else {
                            echo '        indentified as ' . $row['name'] . "\n";
                        }
                        $recognized = [
                            'face_id' => $match['Face']['FaceId'],
                            'gallery' => basename($file->getPath()),
                            'photo' => $file->getBasename('.jpg'),
                        ];
                        $names[] = $recognized;
                        $db->write('photos', $recognized);
                    }
                } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
                    echo "      face not found\n";
                }

            }

            // Output the recognition cache so we don't process this file again.
            file_put_contents($namesJsonFile, json_encode($names));

        }

        return $this;

    }

    /**
     * Add Bielmar-formatted photos to a collection.
     *
     * These photos will serve as the baseline for future searches. Results
     * will be saved to the database table "faces".
     *
     * @param string $indexFile The index file containing the photo metadata.
     * @param int    $year      The school year represented by the photos.
     *
     * @return Rekog Allow method chaining.
     *
     * @throws \RuntimeException On error.
     */
    public function index(string $indexFile, int $year = null): Rekog
    {

        if (file_exists($indexFile) === false) {
            throw new \RuntimeException('Index file does not exist.');
        }

        if ($year === null) {
            if (preg_match('/(20\d\d)/', realpath($indexFile), $match) === 1) {
                $year = $match[1];
            } else {
                throw new \RuntimeException('Must provide year or put index file in a path that contains year.');
            }
        }

        $index = new \Ork\Csv\Reader([
            'columns' => ['yearbook', 'directory', 'file', 'class', 'last', 'first', 'unk1', 'homeroom', 'teacher', 'unk3'],
            'file' => $indexFile,
            'header' => false,
            'delimiter' => "\t",
        ]);

        $this->createCollection();

        $db = new Db($this->config);

        foreach ($index as $row) {
            $file = realpath(dirname($indexFile) . '/' . $row['directory'] . '/' . $row['file']);
            if (file_exists($file) === false) {
                throw new \RuntimeException('Missing photo file: ' . $file);
            }
            $externalId = $year . ':' .  $row['directory'] . ':' . $row['file'];
            $face = $this->indexFace($file, $externalId);
            $row = [
                'id' => $face['Face']['FaceId'],
                'name' => trim($row['first'] . ' ' . $row['last']),
                'class' => is_numeric($row['class']) === true ? ($year + 12 - $row['class']) : strtoupper(trim($row['class'])),
                'external_id' => $externalId,
                'metadata' => base64_encode(gzdeflate(json_encode($face))),
            ];
            $action = $db->writeFace($row) ? 'added' : 'duplicate';
            Ansi::printf(
                '{{BLACK:%4d}} {{cyan:%s}} {{white:%s}} {{YELLOW:%s}} {{' . ($action === 'added' ? 'GREEN' : 'red') . ":%s}}\n",
                $index->getLineNumber(),
                $row['id'],
                $row['external_id'],
                $row['name'],
                $action
            );
        }

        return $this;

    }

}
