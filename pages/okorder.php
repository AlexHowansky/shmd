<?php
$order = $this->getOrder($this->getParam());
$menu = ['/' => 'Home'];
require_once '_order_menu.php';
?>
<div class="ui raised container segment">
    <h1 class="ui centered header">Order Placed</h1>
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
                <td class="right aligned"><?= $this->moneyFormat($this->getPriceForSize($size)) ?></td>
                <td class="right aligned"><?= $this->moneyFormat($this->getPriceForSize($size) * $quantity) ?></td>
            </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right aligned">
                    <h1 class="ui header">
                        <?= $this->moneyFormat($order['total']) ?>
                    </h1>
                </th>
            </tr>
        </tfoot>
    </table>
    <div class="ui grid">
        <div class="sixteen wide column right aligned">
            <a href="/" class="ui huge blue button">Continue</a>
        </div>
    </div>
</div>
