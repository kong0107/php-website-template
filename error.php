<?php
require_once './lib/start.php';

$Get->convert(['status' => 'int']);

$page_info = [
    'title' => 'HTTP 錯誤'
];

require 'html-header.php';
?>

<h1>HTTP <?= $Get->status ?> 錯誤</h1>

<?php require 'html-footer.php'; ?>
