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

/**
 * Simple front controller.
 */
class App
{

    const DEFAULT_ERROR_PAGE = '_error';

    const DEFAULT_PAGE = 'index';

    const DEFAULT_PAGE_DIR = __DIR__ . '/../pages';

    const DEFAULT_PAGE_WRAPPER = '_page';

    const DEFAULT_PHOTO_DIR = __DIR__ . '/../public/photos';

    /**
     * The last error that occurred.
     *
     * @var \Exception
     */
    protected $lastError = null;

    /**
     * The page to render.
     *
     * @var string
     */
    protected $page = null;

    /**
     * The page directory.
     *
     * @var string
     */
    protected $pageDir = null;

    /**
     * The name of the wrapper page.
     *
     * @var string
     */
    protected $pageWrapper = self::DEFAULT_PAGE_WRAPPER;

    /**
     * Page parameters.
     *
     * @var array
     */
    protected $params = [];

    /**
     * The base photo directory.
     *
     * @var string
     */
    protected $photoDir = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        try {
            $url = parse_url($_SERVER['REQUEST_URI']);
            $params = explode('/', trim($url['path'], '/'));
            ob_start();
            $this
                ->setPage(array_shift($params))
                ->setParams($params)
                ->render();
        } catch (\Exception $e) {
            ob_end_clean();
            try {
                $this
                    ->setLastError($e)
                    ->setPage(self::DEFAULT_ERROR_PAGE)
                    ->render();
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * Render the page body.
     *
     * @return null
     */
    public function body()
    {
        include $this->getFileForPage($this->getPage());
    }

    /**
     * Get the full path to a named page.
     *
     * @param string $page The page to get.
     *
     * @return string The full path to the page.
     *
     * @throws \Exception If the page does not exist.
     */
    protected function getFileForPage($page)
    {
        $file = $this->getPageDir() . '/' . $page . '.php';
        if (file_exists($file) === false) {
            throw new \Exception('Page "' . $page . '" not found.');
        }
        return $file;
    }

    /**
     * Get the list of available galleries
     *
     * @return \Generator Generates \Shmd\Gallery objects.
     */
    public function getGalleries()
    {
        $galleries = [];
        foreach (new \DirectoryIterator($this->getPhotoDir()) as $item) {
            if ($item->isDir() === true && $item->isDot() === false) {
                $galleries[$item->getFilename()] = $item->getCTime();
            }
        }
        asort($galleries);
        foreach (array_keys($galleries) as $gallery) {
            yield $this->getGallery($gallery);
        }
    }

    /**
     * Get a gallery.
     *
     * @param string $gallery The gallery to get.
     *
     * @return \Shmd\Gallery The gallery.
     */
    public function getGallery($gallery)
    {
        return new Gallery($this, $gallery);
    }

    /**
     * Get the last error that occurred.
     *
     * @return \Eception
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get the current page.
     *
     * @return string The current page.
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get the page directory.
     *
     * @return string The page directory.
     */
    public function getPageDir()
    {
        if ($this->pageDir === null) {
            $this->setPageDir(self::DEFAULT_PAGE_DIR);
        }
        return $this->pageDir;
    }

    /**
     * Get the wrapper page.
     *
     * @return string The wrapper page.
     */
    public function getPageWrapper()
    {
        return $this->pageWrapper;
    }

    /**
     * Get a parameter by index.
     *
     * @param integer $index The index of the parameter to get.
     *
     * @return string The Nth parameter.
     */
    public function getParam($index = 0)
    {
        return $this->params[$index] ?? null;
    }

    /**
     * Get all the parameters.
     *
     * @return array All the parameters.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get the photo directory.
     *
     * @return string The photo directory.
     */
    public function getPhotoDir()
    {
        if ($this->photoDir === null) {
            $this->setPhotoDir(self::DEFAULT_PHOTO_DIR);
        }
        return $this->photoDir;
    }

    /**
     * Get the the photo directory relative to the document root.
     *
     * @return string The photo directory relative to the document root.
     */
    public function getRelativePhotoDir()
    {
        if (strpos($this->getPhotoDir(), $_SERVER['DOCUMENT_ROOT']) === false) {
            throw new \Exception('Photo dir must be located under DOCUMENT_ROOT.');
        }
        return substr($this->getPhotoDir(), strlen($_SERVER['DOCUMENT_ROOT']));
    }

    /**
     * Render this page.
     *
     * @return null
     */
    public function render()
    {
        include $this->getFileForPage($this->getPageWrapper());
    }

    /**
     * Set configuration values.
     *
     * @param array $config The key/value pairs to set.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setConfig(array $config)
    {
        foreach ($config as $k => $v) {
            if (array_key_exists($k, $this->config) === false) {
                throw new \Exception('Invalid configuration parameter "' . $k . '".');
            }
            $this->config[$k] = $v;
        }
        return $this;
    }

    /**
     * Set the last error that occurred.
     *
     * @param \Exception $e The exception that just occurred.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setLastError(\Exception $e)
    {
        $this->lastError = $e;
        return $this;
    }

    /**
     * Set the page to render.
     *
     * @param string $page The page to render.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setPage($page)
    {
        $page = trim(strtolower($page));
        if (empty($page) === true) {
            $page = self::DEFAULT_PAGE;
        } else {
            if (preg_match('/^[a-z0-9]$/', $page) === false) {
                throw new \Exception('Invalid page name.');
            }
        }
        $this->page = $page;
        return $this;
    }

    /**
     * Set the page directory.
     *
     * @param string $dir The page directory.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setPageDir($dir)
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid page directory.');
        }
        $this->pageDir = $dir;
        return $this;
    }

    /**
     * Set the page wrapper.
     *
     * @param string $pageWrapper The page wrapper.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setPageWrapper($pageWrapper)
    {
        $this->pageWrapper = $pageWrapper;
        return this;
    }

    /**
     * Set the page parameters.
     *
     * @param array $params The page Parameters
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Set the photo directory.
     *
     * @param string $dir The photo directory.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setPhotoDir($dir)
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid photo directory.');
        }
        $this->photoDir = $dir;
        return $this;
    }

}
