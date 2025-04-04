<?php
require_once __DIR__ . '/lib/init.php';

if (empty($http_response)) { // 被 .htaccess 轉來的
	$status_code = intval($_GET['status'] ?? 404);
	$status_full = http_response_status_full($status_code);
	switch (pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION)) {
	    case 'svg':
	    case 'jpg':
	    case 'jpeg':
	    case 'gif':
	    case 'png': {
	        $type = 'image/svg+xml';
	        $body = file_get_contents('assets/gpp_bad_FILL0_wght400_GRAD0_opsz48.svg');
	        break;
	    }
	    case 'css': {
	        $type = 'text/css';
	        $body = "/* $status_full */";
	        break;
	    }
	    case 'js':
	    case 'mjs': {
	        $type = 'text/javascript';
	        $body = "/* $status_full */";
	        break;
	    }
	    case 'json': {
	        $type = 'application/json';
	        $body = json_encode(array(
	            'status' => $status_code,
	            'statusText' => substr($status_full, 4)
	        ));
	        break;
	    }
	    default: {
	        $type = 'text/html';
	        $body = $status_full;
	        $page_info = array(
	            'html_body' => $status_code === 404 ? '<h1>找不到網頁</h1>' : '無存取權限'
	        );
	    }
	}
	$http_response = array(
	    'status' => $status_code,
	    'type' => $type,
	    'body' => $body
	);
}
else $status_full = http_response_status_full($http_response['status']);


/**
 * 開始輸出
 * HTML 和其他的做不同處理
 */
http_response_code($http_response['status']);

if (empty($http_response['type'])) exit;
if ($http_response['type'] !== 'text/html') {
	header("Content-Type: {$http_response['type']}");
	echo $http_response['body'];
	exit;
}

$page_info = array_merge(array(
	'title' => "錯誤 $status_full",
	'html_body' => "<h1>HTTP 錯誤</h1>$status_full"
), $page_info ?? []);

use_html_template($page_info);



/**
 * 只有這個檔案會用到的函式
 */
function http_response_status_full($code) {
	$codes = file(
	    __DIR__ . '/schema/http-status-codes.txt',
	    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
	);
	foreach ($codes as $line) {
	    if (strpos($line, strval($code)) === 0) {
	        return $line;
	    }
	}
}
