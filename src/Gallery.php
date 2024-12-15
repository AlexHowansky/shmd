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

use Countable;

/**
 * Gallery handler.
 */
class Gallery implements Countable
{

    protected const DESCRIPTION_FILE = 'description';

    protected const HIGHLIGHT_FILE = 'highlight';

    protected const TITLE_FILE = 'title';

    /**
     * The app that spawned us.
     *
     * @var App
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
     * Cache for the directory iterator.
     *
     * @var array
     */
    protected static array $photos = [];

    /**
     * Constructor.
     *
     * @param App    $app  The app that spawned us.
     * @param string $name The name of this gallery.
     */
    public function __construct(App $app = null, string $name = null)
    {
        if ($app !== null) {
            $this->setApp($app);
        }
        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * Get the count of photos in this gallery.
     *
     * @return int The count of photos in this gallery.
     */
    public function count(): int
    {
        return count($this->getPhotos());
    }

    /**
     * Get the app that spawned us.
     *
     * @return App
     *
     * @throws \Exception On error.
     */
    public function getApp(): App
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
    public function getDescription(): string
    {
        return $this->getFileContents(self::DESCRIPTION_FILE);
    }

    /**
     * Get the gallery base directory.
     *
     * @return string The gallery base directory.
     */
    public function getDir(): string
    {
        return $this->dir;
    }

    /**
     * Get the number of identified faces in this gallery.
     *
     * @return int The number of identified faces in this gallery.
     */
    public function getFaceCount(): int
    {
        return $this->app->getDb()->getFaceCountInGallery($this->getName());
    }

    /**
     * Get the contents of a file.
     *
     * @param string $name The name of the file to get.
     *
     * @return string The contents of the file.
     */
    protected function getFileContents(string $name): string
    {
        $file = realpath($this->getDir() . '/' . $name);
        if (file_exists($file) === true) {
            return htmlspecialchars(trim(file_get_contents($file)));
        }
        return '';
    }

    /**
     * Get the highlight photo for this gallery.
     *
     * @return string The highlight photo for this gallery.
     */
    public function getHighlightPhoto(): string
    {
        return $this->getFileContents(self::HIGHLIGHT_FILE) ?: $this->getPhotos()[0];
    }

    /**
     * Get the name of the gallery.
     *
     * @return string The name of the gallery.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the photos in this gallery.
     *
     * @return array The photos in this gallery.
     */
    public function getPhotos(): array
    {
        if (array_key_exists($this->getName(), self::$photos) === false) {
            self::$photos[$this->getName()] = [];
            foreach (new \DirectoryIterator($this->getDir()) as $item) {
                if ($item->isFile() === true && $item->isDot() === false && $item->getExtension() === 'jpg') {
                    self::$photos[$this->getName()][] = $item->getBasename('.jpg');
                }
            }
            asort(self::$photos[$this->getName()]);
        }
        return self::$photos[$this->getName()];
    }

    /**
     * Get the path of this gallery relative to the document root.
     *
     * @return string The path of this gallery relative to the document root.
     */
    public function getRelativePath(): string
    {
        return $this->getApp()->getRelativePhotoDir() . '/' . $this->getName();
    }

    /**
     * Get the gallery title.
     *
     * @return string The gallery title.
     */
    public function getTitle(): string
    {
        return $this->getFileContents(self::TITLE_FILE) ?: $this->getName();
    }

    /**
     * Set the spawning app.
     *
     * @param App $app The spawning app.
     *
     * @return Gallery Allow method chaining.
     */
    public function setApp(App $app): Gallery
    {
        $this->app = $app;
        return $this;
    }

    /**
     * Set the gallery name.
     *
     * @param string $name The gallery name.
     *
     * @return Gallery Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setName(string $name): Gallery
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
        return $this;
    }

}
