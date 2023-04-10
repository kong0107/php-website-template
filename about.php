<?php
require_once './include/start.php';
$Get->convert();

$page_info = [
    'title' => '關於' . SITE_NAME,
    'description' => '',
];

require 'html-header.php';

?>

<div id="about"></div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    fetchText('data/about.md')
    .then(md => $('#about').innerHTML = marked.parse(md));
</script>

<?php require 'html-footer.php'; ?>
