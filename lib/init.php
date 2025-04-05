<?php
if (ini_get('user_ini.filename') !== '.user.ini') {
	require_once __DIR__ . '/utility.php';
	foreach (parse_ini_file(__DIR__ . '/../.user.ini') as $key => $value)
		my_ini_set($key, $value);
}
ini_set('error_log',
	realpath(__DIR__ . '/../var/logs/') . DIRECTORY_SEPARATOR . date('ymd') . '.log'
);

require_once __DIR__ . '/functions.php';
set_error_handler('error_handler');
set_exception_handler('exception_handler');
register_shutdown_function('shutdown_function');

if (isset($_COOKIE['at_hash'])) {
	$current_user = json_file_get(__DIR__ . '/../var/tokens.json', $_COOKIE['at_hash']);
	if (empty($current_user)) {
	    site_log('找不到權杖');
	    my_set_cookie('at_hash');
	}
}

if (isset($current_user) && $current_user->exp < $_SERVER['REQUEST_TIME']) {
	site_log("$current_user->email 的權杖已逾期");
	my_set_cookie('at_hash');
	if ($current_user->refresh_token) {
	    $google = json_file_read('./var/client_secret.json')->web;
		$start = microtime(true);
	    $res = fetch_curl($google->token_uri, array(
	        'method' => 'POST',
	        'body' => array(
	            'grant_type' => 'refresh_token',
	            'client_id' => $google->client_id,
	            'client_secret' => $google->client_secret,
	            'refresh_token' => $current_user->refresh_token
	        )
	    ));
		site_log('重整權杖花了 %d 毫秒', 1000 * (microtime(true) - $start));
	    if ($res['errno']) {
			site_log($res);
			unset($current_user);
		}
	    else {
	        site_log('重整權杖成功');
	        $res['body'] = json_decode($res['body']);
			json_file_write('./var/last_refresh_token.json', $res);
	        $current_user->access_token = $res['body']->access_token;
	        $current_user->exp = $_SERVER['REQUEST_TIME'] + $res['body']->expires_in;
	        json_file_set(__DIR__ . '/../var/tokens.json', $_COOKIE['at_hash'], $current_user);
	    }
	}
	else {
		my_set_cookie('at_hash');
		unset($current_user);
	}
}
