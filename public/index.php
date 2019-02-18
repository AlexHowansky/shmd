<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2019 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

new \Shmd\App(new \Shmd\Config(realpath(__DIR__ . '/../config.json')));
