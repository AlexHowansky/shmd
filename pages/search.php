<?php
$results = $this->search($this->getParam());
if (empty($results)) {
    unset($_COOKIE['lastSearch']);
    setcookie('lastSearch', '', ['expires' => 0, 'path' => '/']);
} else {
    if (preg_match('/^[0-9a-f-]{36}$/', (string) $this->getParam()) !== 1) {
        setcookie('lastSearch', (string) $this->getParam(), ['expires' => 0, 'path' => '/']);
        $_COOKIE['lastSearch'] = $this->getParam();
    }
    if (count($results) === 1) {
        header(
            'Location: ' .
            sprintf(
                '/photo/%s/%s',
                $results[0]['gallery']->getName(),
                urlencode((string) $results[0]['photo'])
            )
        );
    }
}
$menu = ['/' => 'Home'];
require_once '_menu.php';
?>
<?php if (empty($results)): ?>
<div class="ui relaxed list">
    <div class="item">
        <h1 class="ui centered header">No matching photos found.</h1>
    </div>
</div>
<?php else: ?>
<div class="ui cards">
<?php
foreach ($results as $match) {
    $gallery = $match['gallery'];
    $photo = $match['photo'];
    $name = $match['name'] ?? null;
    $id = $match['id'] ?? null;
    require '_card.php';
}
?>
<?php endif; ?>
</div>
