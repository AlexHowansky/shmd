<?php
$results = $this->search($this->getParam());
$menu = ['/' => 'Home'];
require_once '_menu.php';
?>
<?php if (empty($results)): ?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">No matching photos found.</h1>
    </div>
</div>
<?php else: ?>
<div class="ui cards" style="padding: 25px;">
<?php
foreach ($results as $match) {
    $gallery = $match['gallery'];
    $photo = $match['photo'];
    $name = $match['name'];
    require '_card.php';
}
?>
<?php endif; ?>
</div>
