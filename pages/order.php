<?php
$id = $this->createOrder($_POST);
header('Location: /okorder/' . $id);
