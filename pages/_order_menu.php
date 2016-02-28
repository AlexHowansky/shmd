<div class="ui inverted blue attached huge menu">
<?php if (empty($menu) === false && is_array($menu) === true): ?>
<?php foreach ($menu as $href => $label): ?>
    <a class="item" href="<?= $href ?>"><?= $label ?></a>
<?php endforeach; ?>
<?php endif; ?>
</div>
