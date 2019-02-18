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
 * Simple front controller.
 */
class App
{

    use Configurable;

    const DATE_FORMAT = 'M j Y h:i:s a';

    const DEFAULT_ARCHIVE_DIR = __DIR__ . '/../orders/archive';

    const DEFAULT_ERROR_PAGE = 'error';

    const DEFAULT_ORDER_DIR = __DIR__ . '/../orders';

    const DEFAULT_PAGE = 'index';

    const DEFAULT_PAGE_DIR = __DIR__ . '/../pages';

    const DEFAULT_PAGE_WRAPPER = '_page';

    const DEFAULT_PHOTO_DIR = __DIR__ . '/../public/photos';

    const SEARCH_LIMIT = 20;

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
     * Constructor.
     *
     * @param Config $config The configuration.
     */
    public function __construct(Config $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }
        setlocale(LC_MONETARY, $this->config['locale']);
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
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function archiveOrder(string $id): App
    {
        if (rename($this->getFileForOrder($id), $this->getFileForArchive($id)) === false) {
            throw new \Exception('Failed to archive order.');
        }
        return $this;
    }

    /**
     * Render the page body.
     *
     * @return void
     */
    public function body()
    {
        include $this->getFileForPage($this->getPage());
    }

    /**
     * Create a new order.
     *
     * @return string The order ID.
     *
     * @throws \Exception On error.
     */
    public function createOrder(): string
    {

        $order = ['quantity' => []];
        foreach (['gallery', 'photo', 'name'] as $field) {
            if (empty($_POST[$field]) === true) {
                throw new \Exception('Field "' . $field . '" can not be empty.');
            }
            $order[$field] = $_POST[$field];
        }

        $total = 0;
        foreach ($this->getSizes() as $size) {
            $key = 'qty_' . $size;
            if (empty($_POST[$key]) === false) {
                $order['quantity'][$size] = (int) $_POST[$key];
                $total += $order['quantity'][$size] * $this->getPriceForSize($size);
            }
        }

        if ($total === 0) {
            throw new \Exception('No quantities selected.');
        }

        $order['comments'] = $_POST['comments'];
        $order['time'] = time();
        $order['total'] = $total;

        $orderJson = json_encode($order);
        $orderHash = sha1($orderJson);
        if (is_writable($this->getOrderDir()) === false) {
            throw new \Exception('Order directory is not writable.');
        }
        if (file_put_contents($this->getFileForOrder($orderHash), $orderJson) === false) {
            throw new \Exception('Error creating order.');
        }

        if ($this->printReceipt($orderHash) === true) {
            $this->archiveOrder($orderHash);
        }

        return $orderHash;

    }

    /**
     * Get the order archive dir.
     *
     * @return string The order archive dir.
     */
    public function getArchiveDir(): string
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
    protected function getFileForArchive(string $id): string
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
    protected function getFileForOrder(string $id): string
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
    protected function getFileForPage(string $page)
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
     * @return \Generator Generates Gallery objects.
     */
    public function getGalleries(): \Generator
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
     * @return Gallery The gallery.
     */
    public function getGallery(string $gallery): Gallery
    {
        return new Gallery($this, $gallery);
    }

    /**
     * Get the last error that occurred.
     *
     * @return \Exception
     */
    public function getLastError(): \Exception
    {
        return $this->lastError;
    }

    /**
     * Get an order.
     *
     * @param string $id The order ID.
     *
     * @return array The order data.
     *
     * @throws \Exception On error.
     */
    public function getOrder(string $id): array
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
        return $order;
    }

    /**
     * Get the order directory.
     *
     * @return string The order directory.
     */
    public function getOrderDir(): string
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
    public function getOrders(): \Generator
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
    public function getPageDir(): string
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
    public function getPageWrapper(): string
    {
        return $this->pageWrapper;
    }

    /**
     * Get a parameter by index.
     *
     * @param int $index The index of the parameter to get.
     *
     * @return string The Nth parameter.
     */
    public function getParam(int $index = 0): string
    {
        return $this->params[$index] ?? null;
    }

    /**
     * Get all the parameters.
     *
     * @return array All the parameters.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get a list of people in a given photo.
     *
     * @param string $gallery The gallery the photo is in.
     * @param string $photo   The photo to get the list of people in.
     *
     * @return array The people in the photo.
     */
    public function getPeopleInPhoto(string $gallery, string $photo)
    {
        return (new Db($this->config))->getPeopleInPhoto($gallery, $photo);
    }

    /**
     * Get the photo directory.
     *
     * @return string The photo directory.
     */
    public function getPhotoDir(): string
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
     *
     * @throws \Exception On error.
     */
    public function getPriceForSize(string $size)
    {
        if (array_key_exists($size, $this->config['prices']) === false) {
            throw new \Exception('Invalid size.');
        }
        return $this->config['prices'][$size];
    }

    /**
     * Get the the photo directory relative to the document root.
     *
     * @return string The photo directory relative to the document root.
     *
     * @throws \Exception On error.
     */
    public function getRelativePhotoDir(): string
    {
        if (strpos($this->getPhotoDir(), $_SERVER['DOCUMENT_ROOT']) === false) {
            throw new \Exception('Photo dir must be located under DOCUMENT_ROOT.');
        }
        return substr($this->getPhotoDir(), strlen($_SERVER['DOCUMENT_ROOT']));
    }

    /**
     * Get the list of possible print sizes.
     *
     * @return array The list of possible print sizes.
     */
    public function getSizes(): array
    {
        return array_keys($this->config['prices']);
    }

    /**
     * Print an order receipt.
     *
     * @param string $id The order ID.
     *
     * @return bool True if the receipt printed successfully.
     */
    public function printReceipt(string $id): bool
    {
        try {
            $order = $this->getOrder($id);
            $lp = new Epson($this->config);
            $lp
                ->linefeed()
                ->writeLineCenter($this->config['title'], true)
                ->linefeed(2)
                ->writeLabel('Name', $order['name'], true)
                ->linefeed(2)
                ->writeLabel('Time', date(self::DATE_FORMAT, $order['time']))
                ->writeLabel('Order', substr($id, 0, 16))
                ->linefeed()
                ->writeLabel('Gallery', $this->getGallery($order['gallery'])->getTitle())
                ->writeLabel('Photo', $order['photo'])
                ->linefeed();
            $lp->writeLine('Size          Quantity Unit Price Subtotal');
            $lp->writeLine('------------- -------- ---------- --------');
            foreach ($order['quantity'] as $size => $quantity) {
                $lp->writeLine(
                    sprintf(
                        '%-13s %8s %10s %8s',
                        $size,
                        $quantity,
                        money_format('%n', $this->getPriceForSize($size)),
                        money_format('%n', $this->getPriceForSize($size) * $quantity)
                    )
                );
            }
            if (empty($order['comments']) === false) {
                $lp
                    ->linefeed()
                    ->writeLine('Comments:')
                    ->writeLine($order['comments']);
            }
            $lp
                ->linefeed(2)
                ->writeLabel('Total', money_format('%n', $order['total']), true)
                ->linefeed(2)
                ->writeLineCenter('Thank You For Your Support', true)
                ->linefeed(8)
                ->cutPartial();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Render this page.
     *
     * @return void
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
    public function search(string $text): array
    {
        $names = (new Db($this->config))->search(urldecode($text), self::SEARCH_LIMIT);
        if (empty($names) === false) {
            foreach ($names as $index => $name) {
                if ($index >= self::SEARCH_LIMIT) {
                    break;
                }
                $names[$index]['gallery'] = new Gallery($this, $name['gallery']);
            }
            return $names;
        }
        $fileMatches = [];
        foreach ($this->getGalleries() as $gallery) {
            foreach ($gallery->getPhotos() as $photo) {
                if (preg_match('/' . $text . '/i', $photo) === 1) {
                    $fileMatches[] = [
                        'gallery' => $gallery,
                        'photo' => $photo,
                    ];
                    if (count($fileMatches) >= self::SEARCH_LIMIT) {
                        return $fileMatches;
                    }
                }
            }
        }
        return $fileMatches;

    }

    /**
     * Set the order archive directory.
     *
     * @param string $dir The order archive directory.
     *
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setArchiveDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid order archive directory.');
        }
        $this->archiveDir = $dir;
        return $this;
    }

    /**
     * Set the last error that occurred.
     *
     * @param \Exception $e The exception that just occurred.
     *
     * @return App Allow method chaining.
     */
    public function setLastError(\Exception $e): App
    {
        $this->lastError = $e;
        return $this;
    }

    /**
     * Set the order directory.
     *
     * @param string $dir The order directory.
     *
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setOrderDir(string $dir): App
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
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setPage(string $page): App
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
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setPageDir(string $dir): App
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
     * @return App Allow method chaining.
     */
    public function setPageWrapper(string $pageWrapper): App
    {
        $this->pageWrapper = $pageWrapper;
        return this;
    }

    /**
     * Set the page parameters.
     *
     * @param array $params The page parameters.
     *
     * @return App Allow method chaining.
     */
    public function setParams(array $params): App
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Set the photo directory.
     *
     * @param string $dir The photo directory.
     *
     * @return App Allow method chaining.
     *
     * @throws \Exception On error.
     */
    public function setPhotoDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new \Exception('Invalid photo directory.');
        }
        $this->photoDir = $dir;
        return $this;
    }

}
