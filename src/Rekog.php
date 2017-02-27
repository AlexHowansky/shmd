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
 * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-rekognition-2016-06-27.html
 */
class Rekog
{

    use Configurable;

    // The API version to use.
    const API_VERSION = '2016-06-27';

    const FACES_JSON = '.faces.json';
    const NAMES_JSON = '.names.json';

    /**
     * Get an API instance.
     *
     * @return \Aws\Rekognition\RekognitionClient
     */
    protected function getApi(): \Aws\Rekognition\RekognitionClient
    {
        return new \Aws\Rekognition\RekognitionClient([
            'credentials' => [
                'key' => $this->config['aws']['key'],
                'secret' => $this->config['aws']['secret'],
            ],
            'debug' => false,
            'region' => $this->config['aws']['region'],
            'version' => self::API_VERSION,
        ]);
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
     * @returns Rekog Allow method chaining.
     *
     * @throws \RuntimeException On error
     */
    public function identify(string $directory): Rekog
    {

        if (is_dir($directory) === false) {
            throw new \RuntimeException('Unable to open directory.');
        }

        $api = $this->getApi();
        if (in_array($this->config['aws']['collection'], $api->listCollections()->get('CollectionIds')) === false) {
            throw new \RuntimeException('Unknown collection.');
        }

        $db = new Db($this->config);
        $api = $this->getApi();

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

            echo $file->getFileName() . "\n";

            // We'll save the identified people to this JSON file.
            $namesJsonFile = $file->getPathname() . self::NAMES_JSON;
            if (file_exists($namesJsonFile) === true) {
                echo "  already recognized, skipping\n";
                continue;
            }

            // We'll save the detected faces information to this file.
            $facesJsonFile = $file->getPathname() . self::FACES_JSON;
            if (file_exists($facesJsonFile) === true) {
                $faces = json_decode(file_get_contents($facesJsonFile), true);
                echo "  already detected " . count($faces) . " faces\n";
            } else {
                echo "  detecting faces... ";
                $faces = $api->detectFaces([
                    'Attributes' => ['ALL'],
                    'Image' => [
                        'Bytes' => file_get_contents($file->getPathname()),
                    ],
                ])->get('FaceDetails');
                echo "found " . count($faces) . "\n";
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
                    echo "    already created $faceFile\n";
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
                    echo "    face $num saved to $faceFile\n";
                }

                // Look for matches for this face.
                try {
                    $matches = $api->searchFacesByImage([
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
                            echo "        indentified as " . $row['name'] . "\n";
                            $recognized = [
                                'name' => $row['name'],
                                'gallery' => basename($file->getPath()),
                                'photo' => $file->getBasename('.jpg'),
                            ];
                            $names[] = $recognized;
                            $db->write('photos', $recognized);
                        }
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
     * Add a directory of Bielmar-formatted photos to a collection.
     *
     * These photos will serve as the baseline for future searches.
     * Output will be written to faces.csv in the current directory
     * and to the database table "faces".
     *
     * @param string $directory The directory containing the photos.
     * @param array  $grades    Only process these grades.
     *
     * @return Rekog Allow method chaining.
     */
    public function index(string $directory, array $grades): Rekog
    {

        if (is_dir($directory) === false) {
            throw new \RuntimeException('Unable to open directory.');
        }

        $api = $this->getApi();
        if (in_array($this->config['aws']['collection'], $api->listCollections()->get('CollectionIds')) === false) {
            $api->createCollection([
                'CollectionId' => $this->config['aws']['collection'],
            ]);
        }

        if (preg_match('/(20\d\d)$/', $directory, $match) === 1) {
            $year = $match[1];
        } else {
            $year = date('Y');
        }

        $db = new Db($this->config);
        $out = new \Ork\Csv\Writer();

        foreach (empty($grades) === false ? $grades : [9, 10, 11, 12] as $grade) {
            $class = $year + 12 - $grade;
            $dataDir = $directory . '/' . $grade . '/';
            $dataFile = $dataDir . $grade . '.TXT';
            if (file_exists($dataFile) === false) {
                throw new \RuntimeException('Unable to open grade data file: ' . $dataFile);
            }
            $csv = new \Ork\Csv\Reader([
                'file' => $dataFile,
                'header' => false,
                'delimiter' => "\t",
            ]);
            foreach ($csv as $row) {
                $name = trim($row[5]) . ' ' . trim($row[4]);
                $photo = $dataDir . $row[2];
                if (file_exists($photo) === false) {
                    throw new \RuntimeException('Missing photo file: ' . $photo);
                }
                $result = $api->indexFaces([
                    'CollectionId' => $this->config['aws']['collection'],
                    'DetectionAttributes' => ['ALL'],
                    'Image' => [
                        'Bytes' => file_get_contents($photo),
                    ],
                ]);
                $faces = $result->get('FaceRecords');
                $face = array_shift($faces);
                $id = $face['Face']['FaceId'];
                $gender = $face['FaceDetail']['Gender']['Value'];
                $row = [
                    'id' => $id,
                    'name' => $name,
                    'gender' => $gender,
                    'class' => $class,
                    'photo' => $grade . '/' . basename($photo),
                ];
                $db->write('faces', $row);
                $out->write($row);
            }
        }

        return $this;

    }

}
