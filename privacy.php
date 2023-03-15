<?php
require_once './include/start.php';
$Get->convert();

$page_info = [
    'title' => '隱私權政策',
    'description' => SITE_NAME . '的隱私權政策',
];

require 'html-header.php';
// 可參考： https://www.ey.gov.tw/Page/806F6058055F6695
?>

<div class="markdown"><?= file_get_contents('data/privacy.md') ?></div>

<?php require 'html-footer.php'; ?>
