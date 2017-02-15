<?php
$this->printReceipt($this->getParam());
header('Location: /detail/' . $this->getParam());
