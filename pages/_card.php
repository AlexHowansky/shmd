<a class="ui card" href="/photo/<?= $gallery->getName() ?>/<?= urlencode($photo) ?>">
    <div class="image">
        <img src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    </div>
    <div class="content">
        <div class="header"><?= $photo ?></div>
    </div>
</a>
