#!/usr/bin/env php
<?php

/**
 * This script will attempt to identify the people in a photo or photos and
 * write the results into the database.
 *
 * @package   SHMD
 * @copyright 2016-2024 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

ini_set('memory_limit', '2G');

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

if ($argc !== 2) {
    echo "Usage:\n";
    echo "    $argv[0] <file or dir>\n\n";
    echo "Where:\n";
    echo "    <file or dir> A single photo or a directory containing the photos to identify.\n";
    exit;
}

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->identify($argv[1]);
