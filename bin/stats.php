#!/usr/bin/env php
<?php

/**
 * This script will parse the order data and print a summary of orders.
 *
 * @package   SHMD
 * @copyright 2016-2019 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

require_once realpath(__DIR__ . '/../vendor') . '/autoload.php';

setlocale(LC_MONETARY, 'en_US');

$config = new \Shmd\Config(realpath(__DIR__ . '/../config.json'));

$totals = [];

foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath(__DIR__ . '/../orders'))) as $file) {

    if (
        $file->isFile() === false ||
        preg_match('/^[[:xdigit:]]{40}\.json$/', $file->getFileName()) !== 1
    ) {
        continue;
    }

    $json = json_decode(file_get_contents($file->getPathName()), true);
    if ($json === false) {
        continue;
    }

    foreach ($json['quantity'] as $size => $quantity) {
        if (array_key_exists($size, $totals) === false) {
            $totals[$size] = 0;
        }
        $totals[$size] += $quantity;
    }

}

$num = 0;
$tot = 0;
foreach ($totals as $size => $count) {
    $num += $count;
    $tot += $config['prices'][$size] * $count;
    printf(
        "Size: %s\n  Count: %d\n  Total: %s\n\n",
        $size,
        $count,
        money_format('%n', $config['prices'][$size] * $count)
    );
}

printf(
    "Total:\n  Count: %d\n  Total: %s\n",
    $num,
    money_format('%n', $tot)
);
