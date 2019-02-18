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

    const RESET = "\033[0m";

    public static function printf(string $string, ...$args): void
    {
        $codes = [
            'reset' => '0',
            'black' => '30;22',
            'red' => '31;22',
            'green' => '32;22',
            'yellow' => '33;22',
            'blue' => '34;22',
            'magenta' => '35;22',
            'cyan' => '36;22',
            'white' => '37;22',
            'BLACK' => '30;1',
            'RED' => '31;1',
            'GREEN' => '32;1',
            'YELLOW' => '33;1',
            'BLUE' => '34;1',
            'MAGENTA' => '35;1',
            'CYAN' => '36;1',
            'WHITE' => '37;1',
        ];
        printf(
            preg_replace_callback(
                '/\{\{([a-zA-Z]+)(?::([^\{\}]+))?\}\}/',
                function($match) use ($codes) {
                    return array_key_exists($match[1], $codes)
                        ? ("\033[" . $codes[$match[1]] . 'm' . (isset($match[2]) ? ($match[2] . self::RESET) : ''))
                        : $match[0];
                },
                $string
            ),
            ...$args
        );
        echo self::RESET;
    }

}
