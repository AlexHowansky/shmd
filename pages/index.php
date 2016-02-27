<div class="ui inverted blue attached huge menu">
    <div class="right menu">
        <div class="item">
            <div class="ui icon input">
                <input type="text" placeholder="Search...">
                <i class="search link icon"></i>
            </div>
        </div>
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
