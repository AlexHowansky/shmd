<?php
$menu = ['/' => 'Home'];
require_once '_menu.php';
$e = $this->getLastError();
if ($e === null) {
    header('HTTP/1.1 404 Page Not Found');
} else {
    header('HTTP/1.1 500 Internal Server Error');
}
?>
<div style="padding: .5em 2em;">
<?php if ($e === null): ?>
    <h1>Page Not Found</h1>
<?php else: ?>
    <h1><?= $this->getLastError()->getMessage() ?></h1>
<?php endif; ?>
</div>
