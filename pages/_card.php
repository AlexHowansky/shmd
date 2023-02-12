<div class="ui raised card">
    <a class="image" href="/photo/<?= $gallery->getName() ?>/<?= urlencode($photo) ?>">
        <img src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    </a>
    <div class="content">
        <div class="center aligned header">
<?php if ($name ?? null): ?>
            <div class="ui big label">
                <?= $name ?>
            </div>
<?php else: ?>
            <?= $photo ?>
<?php endif; ?>
        </div>
    </div>
</div>
