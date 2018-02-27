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
        <div class="fields ui grid">
            <div class="row">
                <div class="field four wide column">
                    <label>Name</label>
                    <input type="text" id="name" name="name" autocomplete="off">
                </div>
                <div class="field twelve wide column">
                    <label>Comments</label>
                    <input type="text" name="comments" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="fields ui grid">
            <div class="row">
                <div class="field four wide column">
                    <label class="underline">Quantity</label>
                </div>
                <div class="field four wide column">
                    <label class="underline">Size</label>
                </div>
                <div class="field four wide column right aligned">
                    <label class="underline">Price</label>
                </div>
                <div class="field four wide column right aligned">
                    <label class="underline">Subtotal</label>
                </div>
            </div>
        </div>
        <div class="ui grid">
<?php foreach ($this->getSizes() as $index => $size): ?>
            <div class="row">
                <div class="four wide column">
                    <select id="qty_<?= $size ?>" class="ui dropdown" name="qty_<?= $size ?>">
<?php for ($i = 0; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
<?php endfor; ?>
                    </select>
                </div>
                <div class="four wide column middle aligned">
                    <?= preg_replace('/(\d+)/', '$1"', $size) ?>
                </div>
                <div class="four wide column middle aligned right aligned">
                    $<span id="amt_<?= $size ?>"><?=  $this->getPriceForSize($size) ?></span>
                </div>
                <div class="four wide column middle aligned right aligned">
                    $<span id="sub_<?= $size ?>">0</span>
                </div>
            </div>
<?php endforeach; ?>
            <div class="row">
                <div class="sixteen wide column right aligned">
                    <div class="ui labeled huge button">
                        <div id="orderButton" class="ui huge red submit button disabled">Submit Order</div>
                        <div id="totalButton" class="ui basic red label">Total $<span id="total">0</span></div>
                    </div>
                </div>
            </div>
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
