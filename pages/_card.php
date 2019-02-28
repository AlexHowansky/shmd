<a class="ui card" href="/photo/<?= $gallery->getName() ?>/<?= urlencode($photo) ?>">
    <div class="image">
        <img src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    </div>
    <div class="content">
        <div class="center aligned header">
<?php if ($name ?? null): ?>
            <button class="ui big blue image label"><?= $name ?></button>
<?php else: ?>
            <?= $photo ?>
<?php endif; ?>
        </div>
    </div>
</a>
