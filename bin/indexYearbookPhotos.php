#!/usr/bin/env php
<?php

/**
 * This script will parse the yearbook data archive from Bielmar,
 * extract the names and photos for each student, and then seed
 * the Amazon Rekognition engine with them. This will serve as
 * our baseline for future facial recognitions. IDs will be
 * inserted into the SQLite database and also output as CSV.
 */

if ($argc < 2) {
    echo "Usage: $argv[0] <directory> [<grade> <grade> ...]\n";
    exit;
}

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->index($argv[1], array_slice($argv, 2));
