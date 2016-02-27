<?php header('HTTP/1.1 500 Internal Server Error'); ?>
<h1><?= $this->getLastError()->getMessage() ?></h1>
<div>
<?= nl2br($this->getLastError()->getTraceAsString()) ?>
</div>
