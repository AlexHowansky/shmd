<?php
$order = $this->getOrder($this->getParam());
$gallery = $this->getGallery($order['gallery']);
$photo = $order['photo'];
$menu = ['/orders' => 'Orders'];
require_once '_order_menu.php';
?>
<div class="ui raised container segment">
    <img class="ui big centered rounded bordered image" src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    <div class="ui list">
        <div class="item">ID: <?= $order['id'] ?></div>
        <div class="item">Name: <?= $order['name'] ?></div>
        <div class="item">Gallery: <?= $gallery->getTitle() ?></div>
        <div class="item">Photo: <?= $order['photo'] ?></div>
        <div class="item">Date: <?= date(self::DATE_FORMAT, $order['time']) ?></div>
    </div>
    <table class="ui celled table">
        <thead>
            <tr>
                <th>Size</th>
                <th class="right aligned">Quantity</th>
                <th class="right aligned">Unit Price</th>
                <th class="right aligned">Subtotal</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($order['quantity'] as $size => $quantity) :?>
            <tr>
                <td><?= $size ?></td>
                <td class="right aligned"><?= $quantity ?></td>
                <td class="right aligned"><?= money_format('%n', $this->getPriceForSize($size)) ?></td>
                <td class="right aligned"><?= money_format('%n', $this->getPriceForSize($size) * $quantity) ?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right aligned">
                    <h1 class="ui header">
                        <?= money_format('%n', $order['total']) ?>
                    </h1>
                </th>
            </tr>
        </tfoot>
    </table>
    <a href="/receipt/<?= $order['id'] ?>" class="ui huge blue button" type="submit">Print Receipt</a>
    <a href="/archive/<?= $order['id'] ?>" class="ui huge green button" type="submit">Mark As Complete</a>
</div>
