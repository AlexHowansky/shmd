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
 * Configuration trait.
 */
trait Configurable
{

    /**
     * The configuration.
     *
     * @var \Shmd\Config
     */
    protected $config = null;

    /**
     * Constructor.
     *
     * @param \Shmd\Config $config The configuration.
     */
    public function __construct(\Shmd\Config $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Set the configuration.
     *
     * @param \Shmd\Config $config The configuration.
     *
     * @return self Allow method chaining.
     */
    public function setConfig(\Shmd\Config $config): self
    {
        $this->config = $config;
        return $this;
    }

}
