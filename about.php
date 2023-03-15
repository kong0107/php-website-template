<?php
require_once './include/start.php';
$Get->convert();

$page_info = [
    'title' => '關於' . SITE_NAME,
    'description' => '',
];

require 'html-header.php';
?>

<div class="markdown"><?= file_get_contents('data/about.md') ?></div>

<?php require 'html-footer.php'; ?>
