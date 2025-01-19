<?php
$gallery = $this->getParam(0);
$photo = $this->getParam(1);
$this->printPhoto($gallery, $photo);
if (empty($this->config['hotFolderLog']) === false) {
    $result = file_put_contents(
        $this->config['hotFolderLog'],
        json_encode([
            'timestamp' => time(),
            'gallery' => $gallery,
            'photo' => $photo,
        ]) . "\n",
        FILE_APPEND
    );
    if ($result === false) {
        throw new \RuntimeException('Unable to write to hot folder log: ' . $this->config['hotFolderLog']);
    }
}
