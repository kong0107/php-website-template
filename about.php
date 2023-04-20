<?php
require_once './include/start.php';
$Get->convert();

$page_info = [
    'title' => '關於' . SITE_NAME,
    'description' => '',
];

require 'html-header.php';
// 本檔之格式同於 `privacy.php` 和 `terms.php` 。
?>

<div class="markdown">
    <?php readfile('data/about.md'); ?>
</div>

<?php require 'html-footer.php'; ?>
