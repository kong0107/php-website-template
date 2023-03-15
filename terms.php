<?php
require_once './include/start.php';
$Get->convert();

$page_info = [
    'title' => '服務條款',
    'description' => SITE_NAME . '的服務條款',
];

require 'html-header.php';
// 可參考： https://ecssl.pchome.com.tw/sys/cflowex/index/staticPage/CLAUSE
?>

<div class="markdown"><?= file_get_contents('data/terms.md') ?></div>

<?php require 'html-footer.php'; ?>
