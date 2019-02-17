#!/usr/bin/env php
<?php

/**
 * This script will parse the yearbook data archive from Bielmar, extract the
 * names and photos for each person, and seed the Amazon Rekognition engine
 * with them. This will serve as our baseline for future facial recognitions.
 * IDs will be inserted into the SQLite database and also output as CSV.
 *
 * @package   SHMD
 * @copyright 2016-2019 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

$index = $argv[1] ?? './Index.txt';

if (file_exists($index) === false) {
    echo "Usage:\n";
    echo "    ${argv[0]} <index file> [<year>]\n\n";
    echo "Where:\n";
    echo "    <index file> The CSV file containing the photo metadata.\n";
    echo "    <year>       The school year represented by the photos.\n";
    exit;
}

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->index($index, $argv[2] ?? null);
