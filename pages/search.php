<?php $results = $this->search($this->getParam()); ?>
<div class="ui inverted blue attached huge menu">
    <a class="item" href="/">Home</a>
    <div class="right menu">
        <div class="item">
            <div class="ui icon input">
                <input id="search_text" type="text" placeholder="Search...">
                <i id="search_icon" class="search link icon"></i>
            </div>
        </div>
    </div>
</div>
<?php if (empty($results)): ?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">No matching photos found.</h1>
    </div>
</div>
<?php else: ?>
<div class="ui cards" style="padding: 25px;">
<?php
foreach ($results as $match) {
    $gallery = $match['gallery'];
    $photo = $match['photo'];
    include '_card.php';
}
?>
<?php endif; ?>
</div>
