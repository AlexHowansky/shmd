#!/usr/bin/env php
<?php

/**
 * This script will iterate over photos in the given directory, attempt to
 * identify the people in the photo, and write the results into the database.
 *
 * @package   SHMD
 * @copyright 2016-2024 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

ini_set('memory_limit', '2G');

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

(new \Shmd\Rekog(new \Shmd\Config(realpath(__DIR__ . '/../config.json'))))->identify($argv[1] ?? '.');
