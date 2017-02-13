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

    use \Shmd\Configurable;

    // The API version to use.
    const API_VERSION = '2016-06-27';

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
     * Add a directory of Bielmar-formatted photos to a collection.
     *
     * These photos will serve as the baseline for future searches.
     *
     * @param string $directory The directory containing the photos.
     * @param array  $grades    Only process these grades.
     *
     * @return self Allow method chaining.
     */
    public function index(string $directory, array $grades): self
    {

        if (is_dir($directory) === false) {
            throw new \RuntimeException('Unable to open directory.');
        }

        $db = new \Shmd\Db();

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
