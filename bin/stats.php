#!/usr/bin/env php
<?php

setlocale(LC_MONETARY, 'en_US');

$sub = [];

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

    if (array_key_exists($json['size'], $sub) === false) {
        $sub[$json['size']] = [
            'quantity' => 0,
            'price' => 0,
        ];
    }
    $sub[$json['size']]['quantity'] += $json['quantity'];
    $sub[$json['size']]['price'] += $json['price'];

}

$num = 0;
$tot = 0;
foreach ($sub as $size => $data) {
    $num += $data['quantity'];
    $tot += $data['price'];
    printf(
        "Size: %s\n  Count: %d\n  Total: %s\n\n",
        $size,
        $data['quantity'],
        money_format('%n', $data['price'])
    );
}

printf(
    "Total:\n  Count: %d\n  Total: %s\n",
    $num,
    money_format('%n', $tot)
);
