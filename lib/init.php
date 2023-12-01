<?php
define('CONFIG', parse_ini_file(__DIR__ . '/../var/config.ini'));

require_once 'util.php';
set_error_handler('error_handler');
set_exception_handler('site_log');

ini_set('default_charset', 'UTF-8');
date_default_timezone_set(CONFIG['timezone'] ?: 'Asia/Taipei');
mb_regex_encoding('UTF-8');

require_once 'http.php';
require_once 'string.php';
