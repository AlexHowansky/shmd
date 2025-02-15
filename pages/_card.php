<div class="ui raised card">
<?php if ($name ?? null): ?>
    <div class="content">
        <div class="ui center aligned header">
            <?= htmlspecialchars((string) $gallery->getTitle()) ?>
        </div>
    </div>
<?php endif; ?>
    <a class="image" href="/photo/<?= $gallery->getName() ?>/<?= urlencode((string) $photo) ?>">
        <img src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    </a>
    <div class="content">
        <div class="ui center aligned header">
<?php if ($name ?? null): ?>
            <div class="ui big label">
                <?= htmlspecialchars((string) $name) ?>
            </div>
<?php else: ?>
            <?= htmlspecialchars((string) $photo) ?>
<?php endif; ?>
        </div>
    </div>
</div>
