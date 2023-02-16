<div class="ui inverted blue fixed huge menu">
<?php if (empty($menu) === false && is_array($menu) === true): ?>
<?php foreach ($menu as $href => $label): ?>
    <a class="item" href="<?= $href ?>"><?= $label ?></a>
<?php endforeach; ?>
<?php endif; ?>
    <div class="right menu">
        <div class="item">
<?php if (isset($_COOKIE['lastSearch']) === true): ?>
            <div class="ui left action icon input">
                <a class="ui big black button" href="/search/<?= $_COOKIE['lastSearch'] ?>"><?= urldecode($_COOKIE['lastSearch']) ?></a>
<?php else: ?>
            <div class="ui icon input">
<?php endif; ?>
                <input id="search_text" type="text" placeholder="Search...">
                <i id="search_icon" class="search link icon"></i>
            </div>
        </div>
    </div>
</div>
