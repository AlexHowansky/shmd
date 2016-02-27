<?php
$gallery = $this->getGallery($this->getParam(0));
$photo = $this->getParam(1);
?>
<div class="ui inverted blue attached huge menu">
    <a class="item" href="/">Home</a>
    <a class="item" href="/gallery/<?= $gallery->getName() ?>"><?= $gallery->getTitle() ?></a>
    <div class="right menu">
        <div class="item">
            <div class="ui icon input">
                <input type="text" placeholder="Search...">
                <i class="search link icon"></i>
            </div>
        </div>
    </div>
</div>
<div class="ui raised container segment">
    <div class="ui list">
        <div class="item">
            <h1 class="ui centered header"><?= $photo ?></h1>
        </div>
    </div>
    <img class="ui big centered rounded bordered image" src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
</div>
<div class="ui raised container segment">
    <form class="ui form" method="post" action="">
        <input type="hidden" name="folder" value="<?= $folder ?>">
        <input type="hidden" name="photo" value="<?= $photo ?>">
        <div class="fields">
            <div class="field">
                <label>Name</label>
                <input type="text" name="name" placeholder="Name">
            </div>
        </div>
        <div class="fields">
            <div class="field">
                <label>Quantity</label>
                <select class="ui dropdown" name="quantity">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
            <div class="field">
                <label>Size</label>
                <select class="ui dropdown" name="size">
                    <option value="4x6">4x6</option>
                    <option value="5x7">5x7</option>
                    <option value="8x10">8x10</option>
                </select>
            </div>
        </div>
        <button class="ui blue button" type="submit">Order</button>
    </form>
</div>
