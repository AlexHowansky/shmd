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

    const DATE_FORMAT = 'j M Y H:i:s';

    const DEFAULT_ARCHIVE_DIR = __DIR__ . '/../orders/archive';

    const DEFAULT_ERROR_PAGE = 'error';

    const DEFAULT_ORDER_DIR = __DIR__ . '/../orders';

    const DEFAULT_PAGE = 'index';

    const DEFAULT_PAGE_DIR = __DIR__ . '/../pages';

    const DEFAULT_PAGE_WRAPPER = '_page';

    const DEFAULT_PHOTO_DIR = __DIR__ . '/../public/photos';

    const SEARCH_LIMIT = 10;

    /**
     * The order archive dir.
     *
     * @var string
     */
    protected $archiveDir = null;

    /**
     * The last error that occurred.
     *
     * @var \Exception
     */
    protected $lastError = null;

    /**
     * The order directory.
     *
     * @var string
     */
    protected $orderDir = null;

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
     * Prices.
     *
     * @var array
     */
    protected $prices = [
        '4x6' => '2',
        '5x7' => '5',
        '8x10' => '8',
        '13x19' => '20',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        setlocale(LC_MONETARY, 'en_US');
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
     * Archive an order.
     *
     * @param string $id The order ID.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function archiveOrder($id)
    {
        if (rename($this->getFileForOrder($id), $this->getFileForArchive($id)) === false) {
            throw new \Exception('Failed to archive order.');
        }
        return $this;
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
     * Create a new order.
     *
     * @return string The order ID.
     */
    public function createOrder()
    {

        $order = [];
        foreach (['gallery', 'photo', 'name', 'quantity', 'size'] as $field) {
            if (empty($_POST[$field]) === true) {
                throw new \Exception('Field "' . $field . '" can not be empty.');
            }
            $order[$field] = $_POST[$field];
        }
        $order['time'] = microtime(true);
        $orderJson = json_encode($order);
        $orderHash = sha1($orderJson);
        if (is_writable($this->getOrderDir()) === false) {
            throw new \Exception('Order directory is not writable.');
        }
        if (file_put_contents($this->getFileForOrder($orderHash), $orderJson) === false) {
            throw new \Exception('Error creating order.');
        }

        $printed = false;
        try {
            $lp = new \Shmd\Epson();
            $lp
                ->linefeed()
                ->writeLineCenter('South High Marathon Dance 2016', true)
                ->linefeed(2)
                ->writeLabel('Name', $order['name'], true)
                ->linefeed(2)
                ->writeLabel('Time', date(self::DATE_FORMAT, $order['time']))
                ->writeLabel('Order', substr($orderHash, 0, 16))
                ->linefeed()
                ->writeLabel('Gallery', $this->getGallery($order['gallery'])->getTitle())
                ->writeLabel('Photo', $order['photo'])
                ->writeLabel('Size', $order['size'])
                ->writeLabel('Quantity', $order['quantity'])
                ->linefeed(2)
                ->writeLabel(
                    'Total Due',
                    money_format('%n', $this->getPriceForSize($order['size']) * $order['quantity']),
                    true
                )
                ->linefeed(2)
                ->writeLineCenter('Thank You For Your Support', true)
                ->linefeed(8)
                ->cutPartial();
            $printed = true;
        } catch (\Exception $e) {
            // nothing
        }

        if ($printed === true) {
            $this->archiveOrder($orderHash);
        }

        return $orderHash;

    }

    /**
     * Get the order archive dir.
     *
     * @return string The order archive dir.
     */
    public function getArchiveDir()
    {
        if ($this->archiveDir === null) {
            $this->setArchiveDir(self::DEFAULT_ARCHIVE_DIR);
        }
        return $this->archiveDir;
    }

    /**
     * Get the full path to an order archive file.
     *
     * @param string $id The order ID.
     *
     * @return string The full path to an order file.
     */
    protected function getFileForArchive($id)
    {
        return $this->getArchiveDir() . '/' . $id . '.json';
    }

    /**
     * Get the full path to an order file.
     *
     * @param string $id The order ID.
     *
     * @return string The full path to an order file.
     */
    protected function getFileForOrder($id)
    {
        return $this->getOrderDir() . '/' . $id . '.json';
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
     * Get an order.
     *
     * @param string $id The order ID.
     *
     * @return array The order data.
     */
    public function getOrder($id)
    {
        $orderFile = $this->getFileForOrder($id);
        if (file_exists($orderFile) === false) {
            throw new \Exception('No such order.');
        }
        $order = json_decode(file_get_contents($orderFile), true);
        if (is_array($order) === false) {
            throw new \Exception('Bad order.');
        }
        $order['id'] = $id;
        $order['total'] = $this->getPriceForSize($order['size']) * $order['quantity'];
        return $order;
    }

    /**
     * Get the order directory.
     *
     * @return string The order directory.
     */
    public function getOrderDir()
    {
        if ($this->orderDir === null) {
            $this->setOrderDir(self::DEFAULT_ORDER_DIR);
        }
        return $this->orderDir;
    }

    /**
     * Get all pending orders.
     *
     * @return array All pending orders.
     */
    public function getOrders()
    {
        $orders = [];
        foreach (new \DirectoryIterator($this->getOrderDir()) as $item) {
            if ($item->isDir() === false && $item->isDot() === false && $item->getExtension() === 'json') {
                $orders[$item->getBasename('.json')] = $item->getCTime();
            }
        }
        arsort($orders);
        foreach (array_keys($orders) as $order) {
            yield $this->getOrder($order);
        }
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
     * Get the price for a photo size.
     *
     * @param string $size The size to get the price for.
     *
     * @return float The price for that size.
     */
    public function getPriceForSize($size)
    {
        if (array_key_exists($size, $this->prices) === false) {
            throw new \Exception('Invalid size.');
        }
        return $this->prices[$size];
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
     * Search for photos.
     *
     * @param string $text The text to search for.
     *
     * @return array The matching photos.
     */
    public function search($text)
    {
        $matches = [];
        foreach ($this->getGalleries() as $gallery) {
            foreach ($gallery->getPhotos() as $photo) {
                if (preg_match('/' . $text . '/i', $photo) === 1) {
                    $matches[] = [
                        'gallery' => $gallery,
                        'photo' => $photo,
                    ];
                    if (count($matches) >= self::SEARCH_LIMIT) {
                        return $matches;
                    }
                }
            }
        }
        return $matches;
    }

    /**
     * Set the order archive directory.
     *
     * @param string $dir The order archive directory.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setArchiveDir($dir)
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid order archive directory.');
        }
        $this->archiveDir = $dir;
        return $this;
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
     * Set the order directory.
     *
     * @param string $dir The order directory.
     *
     * @return \Shmd\App Allow method chaining.
     */
    public function setOrderDir($dir)
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid order directory.');
        }
        $this->orderDir = $dir;
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
            if (preg_match('/^[a-z0-9]+$/', $page) !== 1) {
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
