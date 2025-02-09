<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2024 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

use ArrayAccess;
use RuntimeException;

/**
 * Configuration interface.
 */
class Config implements ArrayAccess
{

    /**
     * Configuration container.
     *
     * @var array
     */
    protected ?array $config = null;

    /**
     * Constructor.
     *
     * @param string $file The configuration file.
     *
     * @throws RuntimeException On error.
     */
    public function __construct(string $file)
    {
        if (file_exists($file) === false) {
            throw new RuntimeException('Missing configuration file.');
        }
        $this->config = json_decode(file_get_contents($file), true);
        if (empty($this->config) === true) {
            throw new RuntimeException('Bad configuration file.');
        }
    }

    /**
     * ArrayAccess interface.
     *
     * @param mixed $offset The offset to check.
     *
     * @return bool True if the offset exists.
     */
    // @codingStandardsIgnoreLine
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->config);
    }

    /**
     * ArrayAccess interface.
     *
     * @param mixed $offset The offset to get.
     *
     * @return mixed The value of the offset.
     *
     * @throws RuntimeException On error.
     */
    // @codingStandardsIgnoreLine
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->offsetExists($offset) === false) {
            throw new RuntimeException('Invalid offset.');
        }
        return $this->config[$offset];
    }

    /**
     * ArrayAccess interface.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value  The value to set.
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->config[$offset] = $value;
    }

    /**
     * ArrayAccess interface.
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[$offset]);
    }

}
