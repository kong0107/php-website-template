<?php
define('CONFIG', parse_ini_file('config/main.ini'));

require_once 'functions.php';
set_error_handler('error_handler');
set_exception_handler('site_log');

date_default_timezone_set(CONFIG['timezone']);
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
mb_http_output('UTF-8');
