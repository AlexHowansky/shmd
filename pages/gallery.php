<?php $gallery = $this->getGallery($this->getParam()); ?>
<div class="ui inverted blue attached huge menu">
    <a class="item" href="/">Home</a>
    <div class="right menu">
        <div class="item">
            <div class="ui icon input">
                <input type="text" placeholder="Search...">
                <i class="search link icon"></i>
            </div>
        </div>
    </div>
</div>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header"><?= $gallery->getTitle() ?></h1>
    </div>
</div>
<div class="ui cards" style="padding: 0 25px 0 25px;">
<?php
foreach ($gallery->getPhotos() as $photo) {
    include '_card.php';
}
?>
</div>
