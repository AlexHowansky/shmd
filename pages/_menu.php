<div class="ui inverted blue attached huge menu">
<?php if (empty($menu) === false && is_array($menu) === true): ?>
<?php foreach ($menu as $href => $label): ?>
    <a class="item" href="<?= $href ?>"><?= $label ?></a>
<?php endforeach; ?>
<?php endif; ?>
    <div class="right menu">
        <div class="item">
            <div class="ui icon input">
                <input id="search_text" type="text" placeholder="Search...">
                <i id="search_icon" class="search link icon"></i>
            </div>
        </div>
    </div>
</div>
