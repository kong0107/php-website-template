<?php
require_once './include/start.php';

$Get->convert();

$page_info = [
    'title' => '', // Google 建議不要叫「首頁」
    'description' => SITE_NAME,
];

require 'html-header.php';
?>

<h1><?= SITE_NAME ?></h1>

<?php if($Session->user): ?>
    您好， <?= $Session->user->givenName ?> 。
    <a href="login.php?logout=1">登出</a>
<?php endif; ?>
<p>範例程式檔請參考 <code>template.php</code>。</p>

<?php require 'html-footer.php'; ?>
