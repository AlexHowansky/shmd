<?php include '_menu.php'; ?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header"><?= $this->config['title'] ?></h1>
    </div>
</div>
<div class="ui huge relaxed celled list">
<?php foreach ($this->getGalleries() as $gallery): ?>
    <div class="item">
        <div class="right floated content">
            <a class="ui big blue button" href="gallery/<?= $gallery->getName() ?>">View</a>
        </div>
        <div class="content">
            <div class="header"><?= $gallery->getTitle() ?></div>
            <div class="description"><?= $gallery->getDescription() ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>
