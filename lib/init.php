<?php
if (ini_get('user_ini.filename') !== '.user.ini') {
	require_once __DIR__ . '/utility/info.php';
	foreach (parse_ini_file(__DIR__ . '/../.user.ini') as $key => $value)
		safe_ini_set($key, $value);
}
ini_set('error_log',
	realpath(__DIR__ . '/../var/logs/') . DIRECTORY_SEPARATOR . date('ymd') . '.log'
);

require_once __DIR__ . '/functions.php';
set_error_handler('error_handler');
set_exception_handler('exception_handler');
register_shutdown_function('shutdown_function');

if (is_readable(__DIR__ . '/../var/config.ini'))
	define('CONFIG', parse_ini_file(__DIR__ . '/../var/config.ini'));
