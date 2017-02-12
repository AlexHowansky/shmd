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
 * Epson receipt printer driver.
 */
class Epson
{

    const DEFAULT_DEVICE = '/dev/usb/lp0';

    const CUT_FULL = "\x1D\x56\x00";
    const CUT_PARTIAL = "\x1D\x56\x01";

    const DOUBLE_HEIGHT_OFF = "\x1B\x21\x00";
    const DOUBLE_HEIGHT_ON = "\x1B\x21\x10";
    const DOUBLE_STRIKE_OFF = "\x1B\x47\x00";
    const DOUBLE_STRIKE_ON = "\x1B\x47\x01";

    const INITIALIZE = "\x1B\x40";

    const JUSTIFY_CENTER = "\x1B\x61\x01";
    const JUSTIFY_LEFT = "\x1B\x61\x00";
    const JUSTIFY_RIGHT = "\x1B\x61\x02";

    const WIDTH = 42;

    /**
     * The printer device.
     *
     * @var string
     */
    protected $device = null;

    /**
     * The printer resource.
     *
     * @var resource
     */
    protected $lp = null;

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_resource($this->lp) === true) {
            fclose($this->lp);
        }
    }

    /**
     * Perform a full cut.
     *
     * @return null
     */
    public function cutFull()
    {
        return $this->write(self::CUT_FULL);
    }

    /**
     * Perform a partial cut.
     *
     * @return null
     */
    public function cutPartial()
    {
        return $this->write(self::CUT_PARTIAL);
    }

    /**
     * Get the default device.
     *
     * @return string The default device.
     */
    public function getDevice()
    {
        if ($this->device === null) {
            $this->setDevice(self::DEFAULT_DEVICE);
        }
        return $this->device;
    }

    /**
     * Get the printer device, opening it if needed.
     *
     * @return resource The printer device.
     */
    protected function getLp()
    {
        if ($this->lp === null) {
            $this->lp = fopen($this->getDevice(), 'wb');
            if (is_resource($this->lp) === false) {
                throw new \Exception('Unable to open printer.');
            }
            $this->write(self::INITIALIZE);
        }
        return $this->lp;
    }

    /**
     * Send a line feed.
     *
     * @param integer $n The number of line feeds to send
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function linefeed($n = 1)
    {
        return $this->write(str_repeat("\n", $n));
    }

    /**
     * Reset the printer to base settings.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function reset()
    {
        return $this->write(self::DOUBLE_HEIGHT_OFF . self::DOUBLE_STRIKE_OFF . self::JUSTIFY_LEFT);
    }

    /**
     * Set the printer device.
     *
     * @param string $device The printer device.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function setDevice($device)
    {
        if (file_exists($device) === false) {
            throw new \Exception('Invalid printer device.');
        }
        $this->device = $device;
        return $this;
    }

    /**
     * Send data to the printer.
     *
     * @param string $string The data to send.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    protected function write($string)
    {
        fwrite($this->getLp(), $string);
        return $this;
    }

    /**
     * Send a label/value line to the printer.
     *
     * @param string  $label The label.
     * @param string  $value The value.
     * @param boolean $bold  True to bold the line.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function writeLabel($label, $value, $bold = false)
    {
        if ($bold === true) {
            $this->write(self::DOUBLE_HEIGHT_ON . self::DOUBLE_STRIKE_ON);
        } else {
            $this->write(self::DOUBLE_HEIGHT_OFF . self::DOUBLE_STRIKE_OFF);
        }
        $len = self::WIDTH - (strlen($label) + strlen($value) + 3);
        return $this->writeLine($label . ': ' . str_repeat('.', $len) . ' ' . $value)->reset();
    }

    /**
     * Send a full line to the printer.
     *
     * @param string $string The string to send.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function writeLine($string)
    {
        return $this->write($string . "\n");
    }

    /**
     * Send a center-aligned line to the printer.
     *
     * @param string  $string The string to send.
     * @param boolean $bold   True to bold the line.
     *
     * @return \Shmd\Epson Allow method chaining.
     */
    public function writeLineCenter($string, $bold = false)
    {
        if ($bold === true) {
            $this->write(self::DOUBLE_HEIGHT_ON . self::DOUBLE_STRIKE_ON);
        } else {
            $this->write(self::DOUBLE_HEIGHT_OFF . self::DOUBLE_STRIKE_OFF);
        }
        return $this->write(self::JUSTIFY_CENTER . $string . "\n")->reset();
    }

}
