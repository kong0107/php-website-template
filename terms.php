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

<div id="terms"></div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    fetchText('data/terms.md')
    .then(md => $('#terms').innerHTML = marked.parse(md));
</script>

<?php require 'html-footer.php'; ?>
