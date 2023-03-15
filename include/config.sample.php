<?php
/**
 * 將本檔案複製為 `config.php` ，並將敏感（涉及安全問題）及常用（頁首頁尾都會需要）的資料寫在那裡。
 */
define('SITE_NAME', 'My Site');
define('LOG_DIR', __DIR__ . '/../logs/'); // It would be safer to put it outside `DocumentRoot`; otherwise `.htaccess` shall be used.

define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

define('GOOGLE_ID', '');
define('GOOGLE_SECRET', '');
define('OAUTH2_CALLBACK', '');

define('POWERED_BY', '睿智數位工作室');
