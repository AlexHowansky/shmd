<?php
$order = $this->getOrder($this->getParam());
$menu = ['/' => 'Home'];
include '_menu.php';
?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">Order Placed</h1>
    </div>
</div>
<div class="ui raised container segment">
    <h1 class="ui header">Total Due: <?= money_format('%n', $order['total']) ?></h1>
    <a class="ui huge blue button" href="/">Continue</a>
</div>
