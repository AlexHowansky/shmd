<?php
$menu = ['/' => 'Home'];
include '_menu.php';
$e = $this->getLastError();
if ($e === null) {
    header('HTTP/1.1 404 Page Not Found');
} else {
    header('HTTP/1.1 500 Internal Server Error');
}
?>
<div style="padding: 25px;">
<?php if ($e === null): ?>
    <h1>Page Not Found</h1>
<?php else: ?>
    <h1><?= $this->getLastError()->getMessage() ?></h1>
    <div>
<?= nl2br($this->getLastError()->getTraceAsString()) ?>
    </div>
<?php endif; ?>
</div>
