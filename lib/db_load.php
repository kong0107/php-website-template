<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/class/PDOi.php';

try {
	$db = new PDOi(
	    'mysql',
	    [
	        'host' => CONFIG['mysqli.hostname'],
	        'dbname' => CONFIG['mysqli.database'],
	        'charset' => 'utf8mb4'
	    ],
	    CONFIG['mysqli.username'],
	    CONFIG['mysqli.password']
	);
	$db->exec(sprintf("SET time_zone = '%s';", date('P')));
}
catch (PDOException $ex) {
	exception_handler($ex, 'Database Error');
}
