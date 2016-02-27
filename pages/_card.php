<div class="card">
    <div class="image">
        <img src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
    </div>
    <div class="content">
        <div class="header">Photo ID: <?= $photo ?></div>
    </div>
    <a href="/photo/<?= $gallery->getName() ?>/<?= $photo ?>" class="ui bottom attached button">
        <i class="shop icon"></i>
        Order
    </a>
</div>
