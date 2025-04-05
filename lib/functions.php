<?php
require_once __DIR__ . '/utility.php';
require_once __DIR__ . '/string.php';

function site_log(
	mixed $target,
	mixed ...$values
) : void {
	$text = is_string($target)
		? (count($values) ? sprintf($target, ...$values) : $target)
		: var_export($target, true)
	;
	$time = date('ymd_His', $_SERVER['REQUEST_TIME']) . substr(bcmod($_SERVER['REQUEST_TIME_FLOAT'], 1, 3), 1);

	file_put_contents(
		ini_get('error_log'),
		"$time {$_SERVER['REQUEST_URI']}\n$text\n\n",
		FILE_APPEND | LOCK_EX
	);
}


function error_handler(
	int $errno,
	string $errstr,
	string $errfile,
	int $errline
) : bool {
	$type_name = array(
		'Error', 'Warning', 'Parse', 'Notice',
		'CoreError', 'CoreWarning', 'CompileError', 'CompileWarning',
		'UserError', 'UserWarning', 'UserNotice',
		'Strict', 'RecoverableError', 'Deprecated', 'UserDeprecated'
	)[intlog($errno)];
	site_log("$type_name: $errstr\nin $errfile:$errline");
	return true;
}


function exception_handler(
	Throwable $ex
) : void {
	$str = get_class($ex);
	if ($code = $ex->getCode()) $str .= "#$code";
	if ($msg = $ex->getMessage()) $str .= ": $msg";
	site_log($str . chr(10) . $ex->getTraceAsString());
	finish(500, '伺服器錯誤');
}


function shutdown_function() {
	global $current_user;
	$str = sprintf('%s + %s bytes sent in %.3f ms; mem peak use %s bytes',
		number_format(ob_get_length()),
		number_format(array_reduce(headers_list(), fn ($sum, $h) => $sum + strlen($h), 0)),
		1000 * (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']),
		number_format(memory_get_peak_usage())
	);

	$request_info = array(
		'user' => $current_user ? $current_user->email : null,
		'tcp_ip' => $_SERVER['REMOTE_ADDR']
	);
	if (preg_match_all('/(Chrome|Firefox|Edge?|Safari|Opera)\/\d+\.\d+/i', $_SERVER['HTTP_USER_AGENT'], $matches))
		$request_info['Navigator'] = array_pop($matches[0]);
	else $request_info['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];

	foreach (array('X-Forwarded-For', 'X-Real-IP', 'Client-IP', 'GeoIP-Country-Code', 'CF-Connecting-IP', 'True-Client-IP', 'Origin', 'Forwarded', 'Via') as $header_name) {
		$key = 'HTTP_' . strtr(strtoupper($header_name), '-', '_');
		if (isset($_SERVER[$key])) $request_info[$header_name] = $_SERVER[$key];
	}
	$str .= "\n" . json_encode($request_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	if (count($_POST)) {
		$copy = $_POST;
		unset($copy['csrf']);
		if (isset($copy['password'])) $copy['password'] = null;
		if (isset($copy['password-again'])) $copy['password-again'] = null;
		$str .= "\n" . json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	site_log($str);

	if ($error = error_get_last()) {
		error_handler($error['type'], $error['message'], $error['file'], $error['line']);
		finish(500, '伺服器錯誤');
	}
}


function finish(
	int $status = 204,
	string $title = '',
	mixed $meta = null
) {
	$error = array(
		'status' => (string) $status
	);
	if ($title) $error['title'] = $title;

	if ($meta) {
		if (gettype($meta) === 'string') {
			$error['detail'] = $meta;
			site_log("$status $title\n$meta");
		}
		else {
			$error['meta'] = $meta;
			site_log("$status $title");
			site_log($meta);
		}
	}
	else if ($title) site_log("$status $title");

	if ($status < 400) {
		http_response_code($status);
		exit(0);
	}
	exit_json(array('errors' => array($error)), $status);
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
	if (! $seconds && ! headers_sent()) header('Location: ' . $url);
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
	if ($mime_type === 'text/html')
	    if (empty($page_info['html_body'])) $page_info['html_body'] = $body;

	$http_response = array(
	    'status' => $status_code,
	    'type' => $mime_type,
	    'body' => $body
	);
	require __DIR__ . '/../error.php';
	exit;
}

/**
 * 載入 `vocabulary.csv` 然後留下有翻譯過的，傳回關聯陣列作為字典。
 */
function load_vocabulary(
) : array {
	$voc = [];
	$lines = explode(chr(10), file_get_contents(__DIR__ . '/../schema/vocabulary.csv'));
	foreach ($lines as $line) {
	    if (! strlen($line)) continue;
	    list($term, $tw) = explode(',', $line);
	    $tw = trim($tw);
	    if (strlen($tw)) $voc[$term] = $tw;
	}
	return $voc;
}
