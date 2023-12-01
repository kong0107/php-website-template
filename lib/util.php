<?php
require_once 'polyfill.php';

/******** 伺服器紀錄 ********/

/**
 * 寫入記錄檔。
 * 可以把單一物件傳進來，或是當成 `printf()` 用。
 */
function site_log(
    /*mixed*/ $target,
    /*mixed*/ ...$values
) : string {
    $text = is_string($target)
        ? (count($values) ? sprintf($target, ...$values) : $target)
        : var_export($target, true)
    ;
    $log_dir = CONFIG['log_dir'] ? CONFIG['log_dir'] : (__DIR__ . '/../var/logs/');
    file_put_contents(
        $log_dir . date('ym') . '.log',
        sprintf("%s %s\n%s\n\n", date(DATE_ATOM), $_SERVER['REQUEST_URI'], $text),
        FILE_APPEND | LOCK_EX
    );
    return $text;
}

/**
 * Strings representing error numbers; used in `error_handler`
 * https://www.php.net/manual/en/errorfunc.constants.php
 */
define('ERROR_CONSTANT_NAMES', array(
    'ERROR', 'WARNING', 'PARSE', 'NOTICE',
    'CORE_ERROR', 'CORE_WARNING',
    'COMPILE_ERROR', 'COMPILE_WARNING',
    'USER_ERROR', 'USER_WARNING', 'USER_NOTICE',
    'STRICT', 'RECOVERABLE_ERROR',
    'DEPRECATED', 'USER_DEPRECATED'
));

/**
 * Function to be registered by `set_error_handler`.
 * https://www.php.net/manual/zh/function.set-error-handler.php
 */
function error_handler(
    int $no,
    string $str,
    string $file,
    int $line
) : void {
    $error_type = ERROR_CONSTANT_NAMES[intval(log($no, 2))];
    site_log("$error_type: $str\non line $line in file $file");
}


/******** 輸出東西到前端 ********/

function use_html_template($page_info = []) {
    global $Get, $Session;
    require __DIR__ . '/../html-header.php';
    echo $page_info['html_body'] ?? '';
    require __DIR__ . '/../html-footer.php';
    exit;
}

function redirect(
    string $url,
    int $seconds = 0
) : void {
    if ($seconds < 0) $seconds = 0;
    if (!$seconds && !headers_sent()) header('Location: ' . $url);
    use_html_template(array(
        'title' => '轉址',
        'html_head' => "
            <meta http-equiv=\"refresh\" content=\"$seconds; url=$url\">
            <script>setTimeout(() => location.href = '$url', {$seconds}000);</script>
        ",
        'html_body' => "預計於 $seconds 秒後跳轉，或請自行點按前往 <a href=\"$url\">$url</a> 。"
    ));
}

/**
 * 傳送狀態碼給前端，依 MIME type 輸出適合的錯誤訊息。
 * 實際運作於 `error.php` ，與 `.htaccess` 導過去的錯誤共用程式碼。
 */
function error_output(
    int $status_code = 500,
    string $body = '',
    string $mime_type = 'text/html',
    array $page_info = []
) : void {
    if($mime_type === 'text/html')
        if(empty($page_info['html_body'])) $page_info['html_body'] = $body;

    $http_response = array(
        'status' => $status_code,
        'type' => $mime_type,
        'body' => $body
    );
    require __DIR__ . '/../error.php';
    exit;
}


/******** 字串處理 ********/

/**
 * 移除路徑中的 `/.` 和 `/..` 。
 * 參考 https://www.php.net/manual/zh/function.realpath.php#84012
 * 但改為最前面會有斜線。
 */
function abspath(
    string $path
) : string {
    $path = str_replace('\\', '/', $path);
    $parts = array_filter(explode('/', $path), 'strlen');
    $needed = array();
    foreach($parts as $p) {
        if($p === '.') continue;
        if($p === '..') array_pop($needed);
        else $needed[] = $p;
    }
    return '/' . implode('/', $needed);
}

function base64url_encode(
    string $string
) : string {
    return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
}

function base64url_decode(
    string $string
) : string {
    return base64_decode(str_pad(
        strtr($string, '-_', '+/'),
        strlen($string) % 4,
        '='
    ));
}

function jwt_decode($token) {
    $parts = explode('.', $token);
    return (object) [
        'header' => json_decode(base64url_decode($parts[0])),
        'payload' => json_decode(base64url_decode($parts[1]))
    ];
}

function parse_dataurl($url) {
    if(preg_match('/^data:(\w+)\/([\w\.\-]+);base64,/', $url, $matches)) {
        return array(
            'type' => $matchs[0],
            'subtype' => $matches[1],
            'base64' => substr($url, strlen($matches[0]))
        );
    }
    else throw new Exception('not a data URL');
}


/******** 載入字典、商品分類、… ********/

/**
 * 載入 `vocabulary.csv` 然後留下有翻譯過的，傳回關聯陣列作為字典。
 */
function load_vocabulary(
) : array {
    $voc = [];
    $lines = explode(chr(10), file_get_contents(__DIR__ . '/../schema/vocabulary.csv'));
    foreach($lines as $line) {
        if(!strlen($line)) continue;
        list($term, $tw) = explode(',', $line);
        $tw = trim($tw);
        if(strlen($tw)) $voc[$term] = $tw;
    }
    return $voc;
}
