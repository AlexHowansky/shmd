<?php
$gallery = $this->getGallery($this->getParam(0));
$photo = urldecode($this->getParam(1));
$people = $this->getPeopleInPhoto($gallery->getName(), $photo);
$menu = [
    '/' => 'Home',
    '/gallery/' . $gallery->getName() => $gallery->getTitle(),
];
require_once '_menu.php';
?>
<div class="ui raised container segment">
    <div class="ui list">
        <div class="item">
            <h1 class="ui centered header"><?= $photo ?></h1>
        </div>
    </div>
    <img class="ui big centered rounded bordered image" src="<?= $gallery->getRelativePath() ?>/<?= $photo ?>.jpg">
</div>
<?php if (empty($people) === false): ?>
<div class="ui raised container segment">
<?php foreach ($people as $person): ?>
    <a class="ui big blue image label" href="/search/<?= $person['id'] ?>">
        <?= $person['name'] ?>
    </a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="ui raised container segment">
    <form class="ui form" method="post" action="/order">
        <input type="hidden" name="gallery" value="<?= $gallery->getName() ?>">
        <input type="hidden" name="photo" value="<?= $photo ?>">
        <div class="fields ui grid">
            <div class="row">
                <div class="field five wide column">
                    <label>Name</label>
                    <input type="text" id="name" name="name" autocomplete="off">
                </div>
                <div class="field eleven wide column">
                    <label>Comments</label>
                    <input type="text" name="comments" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="fields ui grid">
            <div class="row">
                <div class="field two wide column center aligned">
                    <label class="underline">Quantity</label>
                </div>
                <div class="field one wide column center aligned">
                    <label class="underline">Size</label>
                </div>
                <div class="field one wide column center aligned">
                    <label class="underline">Price</label>
                </div>
                <div class="field one wide column center aligned">
                    <label class="underline">Subtotal</label>
                </div>
            </div>
        </div>
        <div class="ui grid">
<?php foreach ($this->getSizes() as $index => $size): ?>
            <div class="row">
                <div class="two wide column">
                    <select id="qty_<?= $size ?>" class="ui dropdown" name="qty_<?= $size ?>">
<?php for ($i = 0; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
<?php endfor; ?>
                    </select>
                </div>
                <div class="one wide column middle aligned">
                    <?= preg_replace('/(\d+)/', '$1"', $size) ?>
                </div>
                <div class="one wide column middle aligned right aligned">
                    $<span id="amt_<?= $size ?>"><?=  $this->getPriceForSize($size) ?></span>
                </div>
                <div class="one wide column middle aligned right aligned">
                    $<span id="sub_<?= $size ?>">0</span>
                </div>
<?php if ($index === 3): ?>
                <div class="eleven wide column right aligned">
                    <div class="ui labeled huge button">
                        <div id="orderButton" class="ui huge red submit button disabled">Submit Order</div>
                        <div id="totalButton" class="ui basic red label">Total $<span id="total">0</span></div>
                    </div>
                </div>
<?php endif; ?>
            </div>
<?php endforeach; ?>
        </div>
    </form>
</div>
<script>
$().ready(function() {

    $('#name').focus();

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

    $('#orderButton').click(function() {
        if ($('.ui.form').form('is valid')) {
            $('#orderButton').addClass('disabled');
        }
    });

    $('select').change(function() {
        var size = $(this).attr('id').replace('qty_', '');
        $('#sub_' + size).html($('#amt_' + size).html() * $(this).val());
        var total = 0;
<?php foreach ($this->getSizes() as $size) :?>
        total += Number($('#sub_<?= $size ?>').html());
<?php endforeach; ?>
        $('#total').html(total);
        if (total == 0) {
            $('#totalButton').removeClass('green');
            $('#totalButton').addClass('red');
            $('#orderButton').removeClass('green');
            $('#orderButton').addClass('red');
            $('#orderButton').addClass('disabled');
        } else {
            $('#totalButton').removeClass('red');
            $('#totalButton').addClass('green');
            $('#orderButton').removeClass('red');
            $('#orderButton').addClass('green');
            $('#orderButton').removeClass('disabled');
        }
    });

});
</script>
