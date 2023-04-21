<?php
require_once './lib/start.php';

$Get->convert();

$page_info = [
    'title' => '', // Google 建議不要叫「首頁」
    'description' => CONFIG['site.name'],
];

require 'html-header.php';
?>

<h1><?= CONFIG['site.name'] ?></h1>

<?php if($Session->user): ?>
    您好， <?= $Session->user->givenName ?> 。
    <a href="login.php?logout=1">登出</a>
<?php endif; ?>
<p>範例程式檔請參考 <code>template.php</code>。</p>

<?php require 'html-footer.php'; ?>
