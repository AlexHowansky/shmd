<?php
$gallery = $this->getGallery($this->getParam());
$menu = ['/' => 'Home'];
include '_menu.php';
?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">Order Placed</h1>
    </div>
</div>
<div style="padding: 25px;">
    <a class="ui huge blue button" href="/">Continue</a>
</div>
