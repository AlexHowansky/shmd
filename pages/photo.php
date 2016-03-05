<?php
$gallery = $this->getGallery($this->getParam(0));
$photo = $this->getParam(1);
$menu = [
    '/' => 'Home',
    '/gallery/' . $gallery->getName() => $gallery->getTitle(),
];
include '_menu.php';
?>
<div class="ui raised container segment">
    <div class="ui list">
        <div class="item">
            <h1 class="ui centered header"><?= $photo ?></h1>
        </div>
    </div>
    <img class="ui big centered rounded bordered image" src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
</div>
<div class="ui raised container segment">
    <form class="ui form" method="post" action="/order">
        <input type="hidden" name="gallery" value="<?= $gallery->getName() ?>">
        <input type="hidden" name="photo" value="<?= $photo ?>">
        <div class="fields">
            <div class="field">
                <label>Name</label>
                <input type="text" name="name" placeholder="Name" autocomplete="off">
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
                    <option value="4x6">4x6 [<?= money_format('%n', $this->getPriceForSize('4x6')) ?> ea.]</option>
                    <option value="5x7">5x7 [<?= money_format('%n', $this->getPriceForSize('5x7')) ?> ea.]</option>
                    <option value="8x10">8x10 [<?= money_format('%n', $this->getPriceForSize('8x10')) ?> ea.]</option>
                    <option value="13x19">13x19 [<?= money_format('%n', $this->getPriceForSize('13x19')) ?> ea.]</option>
                </select>
            </div>
        </div>
        <button class="ui blue button" type="submit">Order</button>
    </form>
</div>
<script>
$().ready(function() {
    $('.ui.form').form({
        fields: {
            name: {
                identifier: 'name',
                rules: [
                    {
                        type: 'empty',
                        prompt: 'Please enter a name.'
                    }
                ]
            }
        },
        inline: true
    });
});
</script>
