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

/**
 * Configuration trait.
 */
trait ConfigurableTrait
{

    /**
     * The configuration.
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Constructor.
     *
     * @param Config $config The configuration.
     */
    public function __construct(?Config $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Set the configuration.
     *
     * @param Config $config The configuration.
     *
     * @return self Allow method chaining.
     */
    public function setConfig(Config $config): self
    {
        $this->config = $config;
        return $this;
    }

}
