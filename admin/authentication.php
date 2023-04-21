<?php
require_once '../lib/start.php';

if(!$Session->user) redirect('../login.php');

$sql = 'SELECT COUNT(*) FROM Person WHERE ' . mysqlii::join_assoc([
    'identifier' => $Session->user->identifier,
    'role' => 'admin'
]);

if(!$db->get_one($sql)) {
    http_response_code(403);
    simple_html('您沒有管理權限，請聯絡網站管理者以取得權限。');
}
