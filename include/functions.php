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
    require_once 'config.php';

    $text = is_string($target)
        ? (count($values) ? sprintf($target, ...$values) : $target)
        : var_export($target, true)
    ;
    $fp = fopen(LOG_DIR . date('ym') . '.log', 'a');
    fprintf($fp, "%s %s\n%s\n\n", date(DATE_ATOM), $_SERVER['REQUEST_URI'], $text);
    fclose($fp);
    return $text;
}

/**
 * Strings representing error numbers; used in `error_handler`
 * https://www.php.net/manual/en/errorfunc.constants.php
 */
define('ERROR_CONSTANT_NAMES', [
    'ERROR', 'WARNING', 'PARSE', 'NOTICE',
    'CORE_ERROR', 'CORE_WARNING',
    'COMPILE_ERROR', 'COMPILE_WARNING',
    'USER_ERROR', 'USER_WARNING', 'USER_NOTICE',
    'STRICT', 'RECOVERABLE_ERROR',
    'DEPRECATED', 'USER_DEPRECATED'
]);

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


/******** HTTP & HTML ********/

/**
 * 輸出一段文字然後就結束頁面，可以附加 HTTP 狀態碼。
 */
function simple_html(
    string $body,
    string $head,
    int $response_code = 0
) : void {
    if($response_code) http_response_code($response_code);
    echo '<!DOCTYPE html><html lang="zh-Hant-TW"><head><meta charset="UTF-8">', $head, '</head><body>', $body, '</body></html>';
    exit;
}

/**
 * Redirect to the specified URL instantly or after some seconds.
 */
function redirect(
    string $url,
    int $seconds = 0
) : void {
    if($seconds < 0) $seconds = 0;
    if(!$seconds && !headers_sent()) header('Location: ' . $url);
    simple_html(
        "若未於 $seconds 秒後跳轉，請自行點按前往 <a href=\"$url\">$url</a> 。",
        "<meta http-equiv=\"refresh\" content=\"$seconds; url=$url\">"
        . "<script>setTimeout(() => location.href = '$url', {$seconds}000);</script>"
    );
}

/**
 * 發出一個 HTTP Post ，並回傳檔頭與內容。
 */
function http_post(
    string $url,
    array $content,
    ?array &$meta = null, //< 回傳的檔頭
    array $header = ['Content-Type' => 'application/x-www-form-urlencoded']
) /*: string|false*/ {
    $package = [
        'method' => 'POST',
        'content' => http_build_query($content)
    ];
    if(count($header)) {
        if(!array_is_list($header)) {
            $header = array_map(
                function($k, $v) { return "$k: $v"; },
                array_keys($header),
                array_values($header)
            );
        }
        $package['header'] = $header;
    }
    $context = stream_context_create(['http' => $package]);

    // return file_get_contents($url, false, $context);
    $stream = fopen($url, 'r', false, $context);
    if($stream === false)
        throw new Exception('Failed to request ' . $url);

    $meta = stream_get_meta_data($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    return $contents;
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
