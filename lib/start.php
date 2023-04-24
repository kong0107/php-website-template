<?php
require_once 'init.php';

// 檢查與啟動 Session 。
switch (session_status()) {
    case PHP_SESSION_DISABLED: {
        site_log('session disabled');
        http_response_code(500);
        exit;
    }
    case PHP_SESSION_NONE: {
        ini_set('session.gc_maxlifetime', 604800);
        session_set_cookie_params(604800);
        session_start();
        break;
    }
    case PHP_SESSION_ACTIVE: {
        site_log('warning: session has already been started; cannot set `session.cookie_lifetime` in ' . __FILE__);
    }
}


/**
 * 資料庫連線。
 * 使用「持續連線」，讓網頁伺服器不用每次都重新跟資料庫做連線。
 * 若不想用或不能用持續連線，則建議在資料庫操作結束後就結束連線，以節省資源。
 *
 * > To open a persistent connection you must prepend `p:` to the hostname when connecting.
 * --- https://www.php.net/manual/en/mysqli.persistconns.php
 *
 * > for every child that opened a persistent connection will have its own open persistent connection to the server.
 * --- https://www.php.net/manual/en/features.persistent-connections.php
 */
if (CONFIG['mysqli.hostname'] && CONFIG['mysqli.username']) {
    require_once 'database.php';
    try {
        $db = new mysqlii(
            CONFIG['mysqli.hostname'],
            CONFIG['mysqli.username'],
            CONFIG['mysqli.password'],
            CONFIG['mysqli.database'],
            CONFIG['mysqli.port']
        );
        $db->set_charset('utf8mb4');
        $db->query(sprintf("SET time_zone = '%s';", date('P')));
    } catch (mysqli_sql_exception $e) {
        site_log('MySQL Error %d: %s', $e->getCode(), $e->getMessage());
        http_response_code(500);
        exit;
    }
}


/**
 * 將常用的預設全域變數轉為自定的物件，方便操作。
 * 注意建構式中的第二個引數，這表示修改 $Get->x 並不會改變 $_GET['x'] ，但是修改 $Session->y 會改變 $_SESSION['y'] 。
 */
require_once __DIR__ . '/associative.php';
$Get = new Associative($_GET);
$Post = new Associative($_POST);
$Session = new Associative($_SESSION, true);


/**
 * 確認是否仍在登入狀態。
 * 登入的處理另見 `/login.php` 。
 */
if (($user = $Session->user)
    && ($user->access_expire < $_SERVER['REQUEST_TIME'])
) {
    if ($user->refresh_token) {
        try {
            $contents = http_post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'client_id' => CONFIG['google.id'],
                'client_secret' => CONFIG['google.secret'],
                'refresh_token' => $user->refresh_token
            ], $meta);
        }
        catch (Throwable $e) {
            site_log('重新整理 %s 的存取權杖失敗。', $user->email);
            $db->insert('log_login', [
                'person' => $user->identifier,
                'action' => 'logout-by-refresh-failure',
                'remote_addr' => $_SERVER['REMOTE_ADDR'],
                'request_headers' => json_encode(apache_request_headers(), JSON_UNESCAPED_SLASHES)
            ]);
            unset($Session->user);
        }

        if (!$contents) site_log('非預期：HTTP 成功，但是重新整理存取權杖失敗？');
        site_log('重新整理 %s 的存取權杖成功。', $user->email);
        // site_log($meta);

        $result = json_decode($contents);
        $db->insert('log_login', [
            'person' => $user->identifier,
            'action' => 'refresh',
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'request_headers' => json_encode(apache_request_headers(), JSON_UNESCAPED_SLASHES)
        ]);
        $Session->user->access_token = $result->access_token;
        $Session->user->access_expire = $_SERVER['REQUEST_TIME'] + $result->expires_in;
    }
    else {
        site_log('%s 的存取權杖逾期，將其登出。', $user->email);
        $db->insert('log_login', [
            'person' => $user->identifier,
            'action' => 'logout-by-expiration',
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'request_headers' => json_encode(apache_request_headers(), JSON_UNESCAPED_SLASHES)
        ]);
        unset($Session->user);
    }
}
