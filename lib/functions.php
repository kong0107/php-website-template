<?php
/**
 * Functions for this project.
 */
foreach (array('polyfill', 'utility') as $dir) {
	foreach (scandir($dir) as $filename) {
		if (substr($filename, -4) !== '.php') continue;
		require_once __DIR__ . "/$dir/$filename";
	}
}


/**
 * Append message to `error_log`
 * @param mixed $target
 * @param mixed ...$values Used as arguments for `sprintf` if and only if `target` is string
 * @return int bytes written
 */
function site_log($target, ...$values) {
	$text = is_string($target)
		? (count($values) ? sprintf($target, ...$values) : $target)
		: var_export($target, true)
	;
	$time = date('ymd_His', $_SERVER['REQUEST_TIME']) . substr(bcmod($_SERVER['REQUEST_TIME_FLOAT'], 1, 3), 1);

	return file_put_contents(
		ini_get('error_log'),
		"$time {$_SERVER['REQUEST_URI']}\n$text\n\n",
		FILE_APPEND | LOCK_EX
	);
}


/**
 * Error handler to be set by `set_error_handler()`
 * @param int $number
 * @param string $message
 * @param string $filepath
 * @param int $line
 * @return true To skip the normal error handler
 */
function error_handler($number, $message, $filepath, $line) {
	$type_name = array(
		'Error', 'Warning', 'Parse', 'Notice',
		'CoreError', 'CoreWarning', 'CompileError', 'CompileWarning',
		'UserError', 'UserWarning', 'UserNotice',
		'Strict', 'RecoverableError', 'Deprecated', 'UserDeprecated'
	)[intlog($number)];
	site_log("$type_name: $message\nin $filepath:$line");
	return true;
}


/**
 * Exception handler to set by `set_exception_handler()`
 * @param Throwable $ex
 * @param string $title Not used if called by `set_exception_handler()`
 * @return never
 */
function exception_handler($ex, $title = '') {
	$str = get_class($ex);
	if ($code = $ex->getCode()) $str .= "#$code";
	if ($msg = $ex->getMessage()) $str .= ": $msg";
	site_log($str . chr(10) . $ex->getTraceAsString());
	finish(500, $title);
}


/**
 * Function to set by `register_shutdown_function()`
 * @return void
 */
function shutdown_function() {
	global $current_user;
	$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
	$str = sprintf('%s + %s bytes sent in %.3f ms; mem peak use %s bytes',
		number_format(ob_get_length()),
		number_format(array_reduce(headers_list(), fn ($sum, $h) => $sum + strlen($h), 0)),
		1000 * (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']),
		number_format(memory_get_peak_usage())
	);

	$request_info = array(
		'user' => isset($current_user) ? $current_user->email : null,
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

	if (! empty($_POST)) {
		$copy = $_POST;
		unset($copy['csrf']);
		if (isset($copy['password'])) $copy['password'] = null;
		if (isset($copy['password-again'])) $copy['password-again'] = null;
		$str .= "\n\$_POST = " . json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	if (! empty($_FILES)) {
		$copy = array();
		foreach ($_FILES as $file) {
			unset($file['full_path']);
			$copy[] = $file;
		}
		$str .= "\n\$_FILES = " . json_encode($copy, $json_flags);
	}
	site_log($str);

	if ($err = error_get_last()) {
		error_handler($err['type'], $err['message'], $err['file'], $err['line']);
		finish(500);
	}
}


/**
 * Exit after sending an HTTP status code, maybe with error message
 * @see https://jsonapi.org/format/#error-objects
 * @param int $status
 * @param string $title
 * @param mixed $meta
 * @return never
 */
function finish($status = 204, $title = '', $meta = null) {
	$obj = array(
		'status' => (string) $status
	);
	if ($title) $obj['title'] = $title;

	if ($meta) {
		if (gettype($meta) === 'string') {
			$obj['detail'] = $meta;
			site_log("$status $title\n$meta");
		}
		else {
			$obj['meta'] = $meta;
			site_log("$status $title");
			site_log($meta);
		}
	}
	else if ($title) site_log("$status $title");

	if ($status < 400) {
		http_response_code($status);
		exit(0);
	}
	exit_json(array('errors' => array($obj)), $status);
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
