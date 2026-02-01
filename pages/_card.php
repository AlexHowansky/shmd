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
<?php if ($id ?? null): ?>
            <a class="ui big blue label" href="/search/<?= $id ?>">
                <i class="search plus icon"></i>
                <?= htmlspecialchars((string) $name) ?>
            </a>
<?php else: ?>
            <div class="ui big label">
                <?= htmlspecialchars((string) $name) ?>
            </div>
<?php endif; ?>
<?php else: ?>
            <?= htmlspecialchars((string) $photo) ?>
<?php endif; ?>
        </div>
    </div>
</div>
