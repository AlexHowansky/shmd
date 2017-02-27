<?php

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

new \Shmd\App(
    new \Shmd\Config(realpath(__DIR__ . '/../config.json'))
);
