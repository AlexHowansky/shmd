<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016-2017 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

/**
 * Configuration interface.
 */
class Config implements \ArrayAccess
{

    /**
     * Configuration container.
     *
     * @var array
     */
    protected $config = null;

    /**
     * Constructor.
     *
     * @param string $file The configuration file.
     *
     * @throws \RuntimeException On error.
     */
    public function __construct(string $file)
    {
        if (file_exists($file) === false) {
            throw new \RuntimeException('Missing configuration file.');
        }
        $this->config = json_decode(file_get_contents($file), true);
        if ($this->config === false) {
            throw new \RuntimeException('Bad configuration file.');
        }
    }

    /**
     * ArrayAccess interface.
     *
     * @param string $offset The offset to check.
     *
     * @return bool True if the offset exists.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->config);
    }

    /**
     * ArrayAccess interface.
     *
     * @param string $offset The offset to get.
     *
     * @return mixed The value of the offset.
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset) === false) {
            throw new \RuntimeException('Invalid offset.');
        }
        return $this->config[$offset];
    }

    /**
     * ArrayAccess interface.
     *
     * @param string $offset The offset to set.
     * @param mixed  $value  The value to set.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    /**
     * ArrayAccess interface.
     *
     * @param string $offset The offset to unset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }

}
