<?php
$order = $this->getOrder($this->getParam());
$gallery = $this->getGallery($order['gallery']);
$photo = $order['photo'];
$menu = ['/orders' => 'Orders'];
include '_order_menu.php';
?>
<div class="ui raised container segment">
    <img class="ui big centered rounded bordered image" src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    <div class="ui list">
        <div class="item">Name: <?= $order['name'] ?></div>
        <div class="item">Gallery: <?= $gallery->getTitle() ?></div>
        <div class="item">Photo: <?= $order['photo'] ?></div>
        <div class="item">Quantity: <?= $order['quantity'] ?></div>
        <div class="item">Size: <?= $order['size'] ?></div>
        <div class="item">Time: <?= date('g:i a', $order['time']) ?></div>
    </div>
</div>
<div class="ui raised container segment">
    <h1 class="ui header">Total Due: <?= money_format('%n', $order['total']) ?></h1>
    <a href="#" class="ui huge red button" type="submit">Void</a>
    <a href="#" class="ui huge green button" type="submit">Complete</a>
</div>
