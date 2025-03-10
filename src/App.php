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

use DirectoryIterator;
use Exception;
use GdImage;
use Generator;
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\UriPrintConnector;
use NumberFormatter;

/**
 * Simple front controller.
 */
class App
{

    use ConfigurableTrait;

    protected const string DATE_FORMAT = 'M j Y h:i:s a';

    protected const string DEFAULT_ARCHIVE_DIR = __DIR__ . '/../orders/archive';

    protected const string DEFAULT_ERROR_PAGE = 'error';

    protected const string DEFAULT_ORDER_DIR = __DIR__ . '/../orders';

    protected const string DEFAULT_PAGE = 'index';

    protected const string DEFAULT_PAGE_DIR = __DIR__ . '/../pages';

    protected const string DEFAULT_PAGE_WRAPPER = '_page';

    protected const string DEFAULT_PHOTO_DIR = __DIR__ . '/../public/photos';

    protected const string DEFAULT_STAGING_DIR = __DIR__ . '/../staging';

    protected const int SEARCH_LIMIT = 100;

    /**
     * The order archive dir.
     */
    protected ?string $archiveDir = null;

    /**
     * The database connector.
     */
    protected ?Db $db = null;

    /**
     * The last error that occurred.
     */
    protected ?Exception $lastError = null;

    /**
     * The order directory.
     */
    protected ?string $orderDir = null;

    /**
     * The page to render.
     */
    protected ?string $page = null;

    /**
     * The page directory.
     */
    protected ?string $pageDir = null;

    /**
     * The name of the wrapper page.
     */
    protected string $pageWrapper = self::DEFAULT_PAGE_WRAPPER;

    /**
     * Page parameters.
     */
    protected array $params = [];

    /**
     * The base photo directory.
     */
    protected ?string $photoDir = null;

    /**
     * The photo staging directory.
     */
    protected ?string $stagingDir = null;

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
        setlocale(LC_MONETARY, $this->config['locale']);
        date_default_timezone_set($this->config['timezone']);
        try {
            $url = parse_url((string) $_SERVER['REQUEST_URI']);
            $params = explode('/', trim($url['path'], '/'));
            ob_start();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->setPageWrapper('_null');
            }
            $this
                ->setPage(array_shift($params))
                ->setParams($params)
                ->render();
        } catch (Exception $e) {
            ob_end_clean();
            try {
                $this
                    ->setLastError($e)
                    ->setPage(self::DEFAULT_ERROR_PAGE)
                    ->setPageWrapper(self::DEFAULT_PAGE_WRAPPER)
                    ->render();
            } catch (Exception $e) {
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
     * @throws Exception On error.
     */
    public function archiveOrder(string $id): App
    {
        if (rename($this->getFileForOrder($id), $this->getFileForArchive($id)) === false) {
            throw new Exception('Failed to archive order.');
        }
        return $this;
    }

    /**
     * Render the page body.
     *
     * @return void
     */
    public function body(): void
    {
        include $this->getFileForPage($this->getPage());
    }

    /**
     * Create a new order.
     *
     * @return string The order ID.
     *
     * @throws Exception On error.
     */
    public function createOrder(): string
    {

        $order = ['quantity' => []];
        foreach (['gallery', 'photo', 'name'] as $field) {
            if (empty($_POST[$field]) === true) {
                throw new Exception('Field "' . $field . '" can not be empty.');
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
            throw new Exception('No quantities selected.');
        }

        $order['comments'] = $_POST['comments'];
        $order['time'] = time();
        $order['total'] = $total;

        $orderJson = json_encode($order);
        $orderHash = sha1($orderJson);
        if (is_writable($this->getOrderDir()) === false) {
            throw new Exception('Order directory is not writable.');
        }
        if (file_put_contents($this->getFileForOrder($orderHash), $orderJson) === false) {
            throw new Exception('Error creating order.');
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
     * Get the database connector.
     *
     * @return Db The database connector.
     */
    public function getDb(): Db
    {
        if ($this->db === null) {
            $this->db = new Db($this->config);
        }
        return $this->db;
    }

    /**
     * Dither an image so it looks decent on a receipt printer.
     *
     * @param string $file The image to dither.
     *
     * @return GdImage The GD image resource for the dithered image.
     */
    protected function getDitheredImage(string $file)
    {
        $img = imagecreatefromstring(file_get_contents($file));
        $width = imagesx($img);
        $height = imagesy($img);
        $arr = [];
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $arr[$x][$y] = imagecolorat($img, $x, $y);
            }
        }
        $output = imagecreate($width, $height);
        $black = imagecolorallocate($output, 0, 0, 0);
        $white = imagecolorallocate($output, 0xff, 0xff, 0xff);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $old = $arr[$x][$y];
                if ($old > 0xffffff * 0.5) {
                    $new = 0xffffff;
                    imagesetpixel($output, $x, $y, $white);
                } else {
                    $new = 0x000000;
                }
                $quantErr = $old - $new;
                $errDiff = (1 / 8) * $quantErr;
                if (isset($arr[$x + 1][$y]) === true) {
                    $arr[$x + 1][$y] += $errDiff;
                }
                if (isset($arr[$x + 2][$y]) === true) {
                    $arr[$x + 2][$y] += $errDiff;
                }
                if (isset($arr[$x - 1][$y + 1]) === true) {
                    $arr[$x - 1][$y + 1] += $errDiff;
                }
                if (isset($arr[$x][$y + 1]) === true) {
                    $arr[$x][$y + 1] += $errDiff;
                }
                if (isset($arr[$x + 1][$y + 1]) === true) {
                    $arr[$x + 1][$y + 1] += $errDiff;
                }
                if (isset($arr[$x][$y + 2]) === true) {
                    $arr[$x][$y + 2] += $errDiff;
                }
            }
        }
        return $output;
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
     * @throws Exception If the page does not exist.
     */
    protected function getFileForPage(string $page): string
    {
        $file = $this->getPageDir() . '/' . $page . '.php';
        if (file_exists($file) === false) {
            throw new Exception('Page "' . $page . '" not found.');
        }
        return $file;
    }

    /**
     * Get the list of available galleries
     *
     * @return Generator Generates Gallery objects.
     */
    public function getGalleries(): Generator
    {
        $galleries = [];
        foreach (new DirectoryIterator($this->getPhotoDir()) as $item) {
            if ($item->isDir() === true && $item->isDot() === false) {
                $galleries[$item->getFilename()] = match ($this->config['sort']['index']['field'] ?? null) {
                    'time' => $item->getCTime(),
                    default => $item->getFilename(),
                };
            }
        }
        if (($this->config['sort']['index']['direction'] ?? 'ascending') === 'ascending') {
            asort($galleries);
        } else {
            arsort($galleries);
        }
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
     * Get the hot folder.
     *
     * @return string The hot folder.
     */
    public function getHotFolder(): ?string
    {
        return $this->config['hotFolder'] ?? null;
    }

    /**
     * Get the last error that occurred.
     *
     * @return Exception
     */
    public function getLastError(): Exception
    {
        return $this->lastError;
    }

    /**
     * Get an order.
     *
     * @param string  $id      The order ID.
     * @param boolean $archive True to consider archived orders too.
     *
     * @return array The order data.
     *
     * @throws Exception On error.
     */
    public function getOrder(string $id, bool $archive = false): array
    {
        $orderFile = $this->getFileForOrder($id);
        if (file_exists($orderFile) === false) {
            if ($archive === true) {
                $orderFile = $this->getFiLeForArchive($id);
                if (file_exists($orderFile) === false) {
                    throw new Exception('No such order.');
                }
            } else {
                throw new Exception('No such order.');
            }
        }
        $order = json_decode(file_get_contents($orderFile), true);
        if (is_array($order) === false) {
            throw new Exception('Bad order.');
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
     * @return Generator All pending orders.
     */
    public function getOrders(): Generator
    {
        $orders = [];
        foreach (new DirectoryIterator($this->getOrderDir()) as $item) {
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
    public function getPage(): string
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
        return urldecode($this->params[$index] ?? '');
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
    public function getPeopleInPhoto(string $gallery, string $photo): array
    {
        return $this->getDb()->getPeopleInPhoto($gallery, $photo);
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
     * @throws Exception On error.
     */
    public function getPriceForSize(string $size): float
    {
        if (array_key_exists($size, $this->config['prices']) === false) {
            throw new Exception('Invalid size.');
        }
        return $this->config['prices'][$size];
    }

    /**
     * Get the the photo directory relative to the document root.
     *
     * @return string The photo directory relative to the document root.
     *
     * @throws Exception On error.
     */
    public function getRelativePhotoDir(): string
    {
        if (str_contains($this->getPhotoDir(), (string) $_SERVER['DOCUMENT_ROOT']) === false) {
            throw new Exception('Photo dir must be located under DOCUMENT_ROOT.');
        }
        return substr($this->getPhotoDir(), strlen((string) $_SERVER['DOCUMENT_ROOT']));
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
     * Get the staging directory.
     *
     * @return string The staging directory.
     */
    public function getStagingDir(): string
    {
        if ($this->stagingDir === null) {
            $this->setStagingDir(self::DEFAULT_STAGING_DIR);
        }
        return $this->stagingDir;
    }

    /**
     * Format a monetary value.
     *
     * @param float $value The value to format.
     *
     * @return string The formatted value.
     */
    protected function moneyFormat(float $value): string
    {
        static $formatter;
        if ($formatter === null) {
            $formatter = new NumberFormatter($this->config['locale'], NumberFormatter::CURRENCY);
        }
        return $formatter->format($value);
    }

    /**
     * Print a photo immediately.
     *
     * @param string $gallery The gallery that the photo is in.
     * @param string $photo   The name of the photo to print.
     *
     * @return void
     */
    public function printPhoto(string $gallery, string $photo): void
    {
        $sourceFile = sprintf('%s/%s/%s.jpg', $this->getStagingDir(), $gallery, $photo);
        $destFile = sprintf('%s/%s.jpg', $this->getHotFolder(), md5((string) microtime(true)));
        $error = '';
        if (file_exists($sourceFile) === false) {
            $error = 'No such photo.';
        } elseif (file_exists($this->getHotFolder()) === false) {
            $error = 'Hot folder missing.';
        } elseif (copy($sourceFile, $destFile) === false) {
            $error = 'Copy to hot folder failed.';
        }
        if (empty($error) === true) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $error,
            ]);
        }
    }

    /**
     * Print an order receipt.
     *
     * @param string $id The order ID.
     *
     * @return bool True if the receipt printed.
     */
    public function printReceipt(string $id): bool
    {
        if (empty($this->config['receipt']['device'] ?? null) === true) {
            return false;
        }

        $order = $this->getOrder($id);

        try {
            $lp = new Printer(UriPrintConnector::get($this->config['receipt']['device']));
            $lp->initialize();
            $lp->feed(1);

            $lp->setEmphasis(true);
            $lp->setTextSize(2, 2);
            $lp->setJustification(Printer::JUSTIFY_CENTER);
            $lp->text($this->config['title']);
            $lp->feed(2);

            $lp->setEmphasis(true);
            $lp->setTextSize(2, 2);
            $lp->setJustification(Printer::JUSTIFY_LEFT);
            $lp->text('Name: ' . $order['name']);
            $lp->feed(2);

            if ($this->config['receipt']['image'] === true) {
                $image = new GdEscposImage();
                $image->readImageFromGdResource(
                    // @phpstan-ignore argument.type
                    $this->getDitheredImage(
                        realpath(__DIR__ . '/../public/photos/' . $order['gallery'] . '/' . $order['photo'] . '.jpg')
                    )
                );
                $lp->graphics($image);
                $lp->feed(1);
            }

            $lp->setEmphasis(false);
            $lp->setTextSize(1, 1);
            $lp->setJustification(Printer::JUSTIFY_LEFT);
            $lp->text('   Time: ' . date(self::DATE_FORMAT, $order['time']) . "\n");
            $lp->text('  Order: ' . substr($id, 0, 16) . "\n");
            $lp->text('Gallery: ' . $this->getGallery($order['gallery'])->getTitle() . "\n");
            $lp->text('  Photo: ' . $order['photo'] . "\n");
            $lp->feed(1);
            $lp->text("Size          Quantity Unit Price Subtotal\n");
            $lp->text("------------- -------- ---------- --------\n");
            foreach ($order['quantity'] as $size => $quantity) {
                $lp->text(
                    sprintf(
                        "%-13s %8s %10s %8s\n",
                        $size,
                        $quantity,
                        $this->moneyFormat($this->getPriceForSize($size)),
                        $this->moneyFormat($this->getPriceForSize($size) * $quantity)
                    )
                );
            }

            if (empty($order['comments']) === false) {
                $lp->feed(1);
                $lp->text("Comments\n");
                $lp->text("--------\n");
                $lp->text($order['comments'] . "\n");
            }

            $lp->feed(2);
            $lp->setEmphasis(true);
            $lp->setTextSize(2, 2);
            $lp->setJustification(Printer::JUSTIFY_RIGHT);
            $lp->text('Total: ' . $this->moneyFormat($order['total']));
            $lp->feed(3);

            $lp->setEmphasis(true);
            $lp->setTextSize(1, 2);
            $lp->setJustification(Printer::JUSTIFY_CENTER);
            $lp->text("Thank You For Your Support\n");

            $lp->feed(8);
            $lp->cut();
            $lp->close();
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Render this page.
     *
     * @return void
     */
    public function render(): void
    {
        include $this->getFileForPage($this->getPageWrapper());
    }

    /**
     * Search for photos.
     *
     * @param string $text The text to search for.
     *
     * @return array The matching photos.
     *
     * @throws Exception On error.
     */
    public function search(?string $text): array
    {
        if (empty($text) === true) {
            unset($_COOKIE['lastSearch']);
            setcookie('lastSearch', '', ['expires' => 0, 'path' => '/']);
            throw new Exception('Must provide a search term.');
        }
        $names = $this->getDb()->search($text, self::SEARCH_LIMIT);
        if (empty($names) === false) {
            foreach ($names as $index => $name) {
                if ($index >= self::SEARCH_LIMIT) {
                    break;
                }
                $names[$index]['gallery'] = new Gallery($this, $name['gallery']);
            }
            usort($names, fn($a, $b) => strcasecmp((string) $b['photo'], (string) $a['photo']));
            return $names;
        }
        $fileMatches = [];
        foreach ($this->getGalleries() as $gallery) {
            foreach ($gallery->getPhotos() as $photo) {
                if (preg_match('/' . $text . '/i', (string) $photo) === 1) {
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
     * @throws Exception On error.
     */
    public function setArchiveDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new Exception('Invalid order archive directory.');
        }
        $this->archiveDir = $dir;
        return $this;
    }

    /**
     * Set the last error that occurred.
     *
     * @param Exception $e The exception that just occurred.
     *
     * @return App Allow method chaining.
     */
    public function setLastError(Exception $e): App
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
     * @throws Exception On error.
     */
    public function setOrderDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new Exception('Invalid order directory.');
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
     * @throws Exception On error.
     */
    public function setPage(string $page): App
    {
        $page = trim(strtolower($page));
        if (empty($page) === true) {
            $page = self::DEFAULT_PAGE;
        } else {
            if (preg_match('/^[a-z0-9]+$/', $page) !== 1) {
                throw new Exception('Invalid page name.');
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
     * @throws Exception On error.
     */
    public function setPageDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new Exception('Invalid page directory.');
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
        return $this;
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
     * @throws Exception On error.
     */
    public function setPhotoDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new Exception('Invalid photo directory.');
        }
        $this->photoDir = $dir;
        return $this;
    }

    /**
     * Set the staging directory.
     *
     * @param string $dir The staging directory.
     *
     * @return App Allow method chaining.
     *
     * @throws Exception On error.
     */
    public function setStagingDir(string $dir): App
    {
        $dir = realpath($dir);
        if (is_dir($dir) === false) {
            throw new Exception('Invalid staging directory.');
        }
        $this->stagingDir = $dir;
        return $this;
    }

}
