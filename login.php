<?php
/**
 * 本檔案處理：
 * 1. 準備登入。
 * 2. 登出。
 * 3. 登入後的驗證：從 Google 被轉回來。
 *
 * 確認登入狀態、確認未逾時的程式碼另見 `/lib/start.php` 。
 *
 * https://growingdna.com/google-oauth-2-0-for-3rd-party-login/
 * https://developers.google.com/identity/protocols/oauth2/web-server
 * https://developers.google.com/identity/openid-connect/openid-connect
 */
require_once './lib/init.php';

/**
 * 如果是直接連來這一頁，那就轉去 Google 的登入頁。
 */
if (empty($_GET['logout']) && empty($_GET['code'])) {
    switch (session_status()) {
        case PHP_SESSION_DISABLED: {
            site_log('error: session disabled');
            http_response_code(500);
            exit;
        }
        case PHP_SESSION_NONE: {
            session_set_cookie_params(120); // 讓 csrf_token 是短時效
            session_start();
            break;
        }
        case PHP_SESSION_ACTIVE: {
            site_log('warning: session has already been started; could not set `session.cookie_lifetime` in ' . __FILE__);
        }
    }

    /**
     * 加入防止 CSRF 的暫存亂數，後面接上前一頁的位址，以便登入完成後轉址回去。
     */
    $state = $_SESSION['csrf_token'] = base64url_encode(random_bytes(24));
    if (str_starts_with($_SERVER['HTTP_REFERER'], CONFIG['site.root'])) {
        $referer = substr($_SERVER['HTTP_REFERER'], strlen(CONFIG['site.root']));
        if (! str_starts_with($referer, 'login.php')) $state .= $referer;
    }

    $query = http_build_query([
        'access_type' => 'offline',
        'client_id' => CONFIG['google.id'],
        'redirect_uri' => CONFIG['site.root'] . 'login.php',
        'response_type' => 'code',
        'scope' => 'openid profile email',
        'state' => $state
    ]);
    redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
}


/**
 * 這邊才載入其他初始設定，不然會跟上面的 session_start() 衝突。
 */
require_once './lib/start.php';

/**
 * 處理登出。
 */
if ($Get->logout) {
    site_log('%s 主動登出了。', $_SESSION['user']->identifier);
    $db->insert('log_login', [
        'person' => $_SESSION['user']->identifier,
        'action' => 'logout-by-user',
        'remote_addr' => $_SERVER['REMOTE_ADDR'],
        'request_headers' => json_encode(apache_request_headers(), JSON_UNESCAPED_SLASHES)
    ]);
    unset($Session->user);
    redirect('./index.php');
}


/**
 * 處理 OAuth2 登入。
 */
$csrf_token = $Session->csrf_token;
unset($Session->csrf_token); // 先清掉，省得要擔心會再被利用

if (! str_starts_with($Get->state, $csrf_token)) {
    site_log('未通過 STP 測試，可能是逾時或是 CSRF 。');
    redirect('login.php'); // 重新再讀一次本頁，但不帶參數，即可因上面的程式而轉去登入。
}

try {
    $response = http_post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'authorization_code',
        'client_id' => CONFIG['google.id'],
        'client_secret' => CONFIG['google.secret'],
        'code' => $Get->code,
        'redirect_uri' => CONFIG['site.root'] . 'login.php'
    ]);
}
catch (Throwable $e) {
    site_log('未能收到存取權杖。');
    error_output(401, '登入失敗。');
}

$result = json_decode($response['body']);
$id_token = jwt_decode($result->id_token)->payload;

$db->replace('Person', $user = [
    'identifier' => $id_token->sub,
    'email' => $id_token->email,
    'givenName' => $id_token->given_name,
    'familyName' => $id_token->family_name
]);
$user['role'] = $db->get_one("SELECT `role` FROM `Person` WHERE `identifier` = '{$user['identifier']}'");

$db->insert('log_login', [
    'person' => $user['identifier'],
    'action' => 'login',
    'remote_addr' => $_SERVER['REMOTE_ADDR'],
    'request_headers' => json_encode(apache_request_headers(), JSON_UNESCAPED_SLASHES)
]);
site_log('收到 %s 的存取權杖。', $user['email']);

$Session->user = (object) array_merge($user, [
    'refresh_token' => $result->refresh_token ?? null,
    'access_token' => $result->access_token,
    'access_expire' => $id_token->exp
]);

redirect('./' . substr($Get->state, strlen($csrf_token)));
