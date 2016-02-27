<?php $results = $this->search($this->getParam()); ?>
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
<?php if (empty($results)): ?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">No matching photos found.</h1>
    </div>
</div>
<?php else: ?>
<div class="ui cards" style="padding: 25px;">
<?php foreach ($results as $match): ?>
    <div class="card">
        <div class="image">
            <img src="<?= $match['gallery']->getRelativePath() ?>/<?= $match['photo'] ?>.jpg">
        </div>
        <div class="content">
            <div class="header">Photo ID: <?= $match['photo'] ?></div>
        </div>
        <a href="/photo/<?= $match['gallery']->getName() ?>/<?= $match['photo'] ?>" class="ui bottom attached button">
            <i class="shop icon"></i>
            Order
        </a>
    </div>
<?php endforeach; ?>
<?php endif; ?>
</div>
