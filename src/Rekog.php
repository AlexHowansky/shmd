<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2024 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use DirectoryIterator;
use Ork\Csv\Reader;
use RuntimeException;

/**
 * AWS Rekognition interface.
 *
 * See http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-rekognition-2016-06-27.html
 */
class Rekog
{

    use ConfigurableTrait;

    // The API version to use.
    protected const API_VERSION = '2016-06-27';

    // The faces metadata file name extension.
    protected const FACES_JSON = '.faces.json';

    // The names metadata file name extension.
    protected const NAMES_JSON = '.names.json';

    /**
     * An instance of the API client to use.
     *
     * @var RekognitionClient
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
     * @return RekognitionClient
     */
    protected function getApi(): RekognitionClient
    {
        if ($this->api === null) {
            $this->api = new RekognitionClient([
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
     * @throws RuntimeException On error.
     */
    public function identify(string $directory): Rekog
    {

        if (is_dir($directory) === false) {
            throw new RuntimeException('Unable to open directory.');
        }

        if ($this->collectionExists() === false) {
            throw new RuntimeException('Unknown collection.');
        }

        $db = new Db($this->config);

        // Iterate over all the files in the named directory.
        foreach (new DirectoryIterator($directory) as $file) {

            // Skip files that aren't photos.
            if (
                $file->isDot() === true ||
                $file->isFile() === false ||
                $file->getExtension() !== 'jpg'
            ) {
                continue;
            }

            // We'll save the identified people to this JSON file.
            $namesJsonFile = $file->getPathname() . self::NAMES_JSON;
            if (file_exists($namesJsonFile) === true) {
                Ansi::printf("{{BLUE:%s}} {{YELLOW}}already recognized\n", $file->getFileName());
                continue;
            }

            // We'll save the detected faces information to this file.
            $facesJsonFile = $file->getPathname() . self::FACES_JSON;
            if (file_exists($facesJsonFile) === true) {
                $faces = json_decode(file_get_contents($facesJsonFile), true);
                Ansi::printf("{{BLUE:%s}} {{YELLOW}}already detected %d faces\n", $file->getFileName(), count($faces));
            } else {
                Ansi::printf('{{BLUE:%s}} ', $file->getFileName());
                $faces = $this->getApi()->detectFaces([
                    'Attributes' => ['ALL'],
                    'Image' => [
                        'Bytes' => file_get_contents($file->getPathname()),
                    ],
                ])->get('FaceDetails');
                file_put_contents($facesJsonFile, json_encode($faces));
                if (count($faces) > 0) {
                    Ansi::printf("{{GREEN}}found %d faces\n", count($faces));
                } else {
                    Ansi::printf("{{YELLOW}}found no faces\n");
                }
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
                    Ansi::printf("    {{YELLOW}}already created {{BLUE:%s}}\n", $faceFile);
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
                    Ansi::printf("    {{GREEN}}face %d saved to {{BLUE:%s}}\n", $num, $faceFile);
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
                        Ansi::printf("        {{YELLOW}}face not identified\n");
                    } else {
                        $match = array_shift($matches);
                        $row = $db->findFace($match['Face']['FaceId']);
                        if (empty($row) === true) {
                            Ansi::printf("        {{red}}face not in database\n");
                        } else {
                            Ansi::printf(
                                "        {{CYAN:%s}} {{GREEN}}identified as {{WHITE:%s}}\n",
                                $match['Face']['FaceId'],
                                $row['name']
                            );
                        }
                        $recognized = [
                            'face_id' => $match['Face']['FaceId'],
                            'gallery' => basename(realpath($file->getPath())),
                            'photo' => $file->getBasename('.jpg'),
                        ];
                        $names[] = $recognized;
                        $db->writePhoto($recognized);
                    }
                } catch (RekognitionException $e) {
                    Ansi::printf(
                        "        {{red}}face not found: {{white:%s}}\n",
                        json_decode($e->getResponse()->getBody(), true)['Message']
                    );
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
     * @throws RuntimeException On error.
     */
    public function index(string $indexFile, ?int $year = null): Rekog
    {

        if (file_exists($indexFile) === false) {
            throw new RuntimeException('Index file does not exist.');
        }

        if ($year === null) {
            if (preg_match('/(20\d\d)/', realpath($indexFile), $match) === 1) {
                $year = $match[1];
            } else {
                throw new RuntimeException('Must provide year or put index file in a path that contains year.');
            }
        }

        $index = new Reader(
            columnNames: ['u1', 'directory', 'file', 'class', 'last', 'first', 'u2', 'u3', 'u4', 'u5'],
            delimiterCharacter: "\t",
            file: $indexFile,
            hasHeader: false,
        );

        $this->createCollection();

        $db = new Db($this->config);

        foreach ($index as $row) {
            // Support reading either the subdir index or the master index.
            $dir = preg_replace('|/' . $row['directory'] . '$|', '', dirname($indexFile));
            $file = realpath($dir . '/' . $row['directory'] . '/' . $row['file']);
            if (file_exists($file) === false) {
                throw new RuntimeException('Missing photo file: ' . $file);
            }
            $externalId = str_replace(' ', '_', $year . ':' . $row['directory'] . ':' . $row['file']);
            $face = $this->indexFace($file, $externalId);
            $row = [
                'id' => $face['Face']['FaceId'],
                'name' => trim($row['first'] . ' ' . $row['last']),
                'class' => is_numeric($row['class']) === true
                    ? ($year + 12 - $row['class'])
                    : strtoupper(trim((string) $row['class'])),
                'external_id' => $externalId,
                'metadata' => base64_encode(gzdeflate(json_encode($face))),
            ];
            $action = $db->writeFace($row) === true ? 'added' : 'already processed';
            Ansi::printf(
                '{{white:%4d}} {{CYAN:%s}} {{BLUE:%s}} {{WHITE:%s}} {{' .
                ($action === 'added' ? 'GREEN' : 'YELLOW') . ":%s}}\n",
                $index->getLineNumber(),
                $row['id'],
                $row['external_id'],
                $row['name'],
                $action
            );
        }

        return $this;

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

}
