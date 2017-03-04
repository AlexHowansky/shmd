#!/usr/bin/env php
<?php

ini_set('memory_limit', '8G');

/**
 * This script will iterate over photos in the given directory, attempt
 * to identify the people in the photo, and then write the results into
 * the SQLite database. This works better with high resolution sources,
 * so it probably should be run aginst the staging photos and not the
 * public photos.
 */

if ($argc !== 2) {
    echo "Usage: $argv[0] <directory>\n";
    exit;
}

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->identify($argv[1]);
