#!/usr/bin/env php
<?php

/**
 * This script will iterate over newly uploaded photos, attempt
 * to identify the people in the photo, and then write the
 * results into the SQLite database.
 */

if ($argc !== 2) {
    echo "Usage: $argv[0] <directory>\n";
    exit;
}

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->identify($argv[1]);
