<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2019 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

/**
 * ANSI color helper.
 */
class Ansi
{

    public static function printf(string $string, ...$args): int
    {
        $codes = [
            'black' => '30',
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'BLACK' => '30;1',
            'RED' => '31;1',
            'GREEN' => '32;1',
            'YELLOW' => '33;1',
            'BLUE' => '34;1',
            'MAGENTA' => '35;1',
            'CYAN' => '36;1',
            'WHITE' => '37;1',
        ];
        return printf(
            preg_replace_callback(
                '/\{\{([a-zA-Z]+):([^\{\}]+)\}\}/',
                function($match) use ($codes) {
                    return "\033[" . $codes[$match[1]] . 'm' . $match[2] . "\033[0m";
                },
                $string
            ),
            ...$args
        );
    }

}
