<?php require_once '_menu.php'; ?>
<div class="ui cards">
<?php foreach ($this->getGalleries() as $gallery): ?>
    <a class="ui raised card" href="/gallery/<?= $gallery->getName() ?>">
<?php if ($gallery->count()): ?>
        <div class="image">
            <img src="<?= $gallery->getRelativePath() ?>/<?= $gallery->getHighlightPhoto() ?>.jpg">
        </div>
<?php endif; ?>
        <div class="content">
            <div class="center aligned header">
                <?= $gallery->getTitle() ?>
            </div>
            <div class="center aligned description">
                <?= $gallery->getDescription() ?>
            </div>
        </div>
        <div class="extra content">
            <i class="camera icon"></i>
            <?= $gallery->count() ?> Photos
        </div>
    </a>
<?php endforeach; ?>
</div>
