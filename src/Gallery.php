<?php

/**
 * SHMD
 *
 * @package   SHMD
 * @copyright 2016 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/shmd/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/shmd
 */

namespace Shmd;

class Gallery
{

    const DESCRIPTION_FILE = 'description';

    const TITLE_FILE = 'title';

    /**
     * The app that spawned us.
     *
     * @var \Shmd\App
     */
    protected $app = null;

    /**
     * The gallery base directory.
     *
     * @var string
     */
    protected $dir = null;

    /**
     * The gallery name.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Constructor.
     *
     * @param \Shmd\App $app  The app that spawned us.
     * @param string    $name The name of this gallery.
     */
    public function __construct(App $app = null, $name = null)
    {
        if ($app !== null) {
            $this->setApp($app);
        }
        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * Get the app that spawned us.
     *
     * @return \Shmd\App
     */
    public function getApp()
    {
        if ($this->app === null) {
            throw new \Exception('No App object has been set yet.');
        }
        return $this->app;
    }

    /**
     * Get the gallery description.
     *
     * @return string The gallery description.
     */
    public function getDescription()
    {
        return $this->getFileContents(self::DESCRIPTION_FILE);
    }

    /**
     * Get the gallery base directory.
     *
     * @return string The gallery base directory.
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Get the contents of a file.
     *
     * @param string $name The name of the file to get.
     *
     * @return string The contents of the file.
     */
    protected function getFileContents($name)
    {
        $file = realpath($this->getDir() . '/' . $name);
        if (file_exists($file) === true) {
            return htmlspecialchars(trim(file_get_contents($file)));
        }
    }

    /**
     * Get the name of the gallery.
     *
     * @return string The name of the gallery.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the photos in this gallery.
     *
     * @return array The photos in this gallery.
     */
    public function getPhotos()
    {
        $photos = [];
        foreach (new \DirectoryIterator($this->getDir()) as $item) {
            if ($item->isFile() === true && $item->isDot() === false && $item->getExtension() === 'jpg') {
                $photos[$item->getBasename('.jpg')] = $item->getCTime();
            }
        }
        asort($photos);
        return array_keys($photos);
    }

    /**
     * Get the path of this gallery relative to the document root.
     *
     * @return string The path of this gallery relative to the document root.
     */
    public function getRelativePath()
    {
        return $this->getApp()->getRelativePhotoDir() . '/' . $this->getName();
    }

    /**
     * Get the gallery title.
     *
     * @return string The gallery title.
     */
    public function getTitle()
    {
        return $this->getFileContents(self::TITLE_FILE) ?? $this->getName();
    }

    /**
     * Set the spawning app.
     *
     * @param \Shmd\App $app The spawning app.
     *
     * @return \Shmd\Gallery Allow method chaining.
     */
    public function setApp(App $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Set the gallery name.
     *
     * @param string $name The gallery name.
     *
     * @return \Shmd\Gallery Allow method chaining.
     */
    public function setName($name)
    {
        if (preg_match('/^[a-z0-9]+$/', $name) !== 1) {
            throw new \Exception('Invalid gallery.');
        }
        $dir = realpath($this->getApp()->getPhotoDir() . '/' . $name);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid gallery.');
        }
        $this->dir = $dir;
        $this->name = $name;
    }

}
