<?php
$menu = ['/orders' => 'Orders'];
require_once '_order_menu.php';
?>
<div class="ui huge celled list">
<?php foreach ($this->getOrders() as $order): ?>
    <div class="item">
        <div class="right floated content">
            <a class="ui big blue button" href="detail/<?= $order['id'] ?>">View</a>
        </div>
        <div class="content">
            <div class="header"><?= $order['name'] ?></div>
            <div class="description"><?= date('g:i a', $order['time']) ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<script>
$().ready(function() {
    setTimeout(function() { window.location.reload(1); }, 60000);
});
</script>
