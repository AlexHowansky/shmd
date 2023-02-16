<?php
$gallery = $this->getGallery($this->getParam());
$menu = ['/' => 'Home'];
require_once '_menu.php';
?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header"><?= $gallery->getTitle() ?></h1>
    </div>
</div>
<div class="ui cards">
<?php
foreach ($gallery->getPhotos() as $photo) {
    require '_card.php';
}
?>
</div>
