<?php

function array_remove_null(
	array &$array
) : int {
	$count = 0;
	$is_list = array_is_list($array);
	foreach ($array as $key => &$child) {
		if (is_array($child)) {
			$count += array_remove_null($child);
		}
		else if (is_null($child)) {
			unset($array[$key]);
			++$count;
		}
	}
	if ($is_list) $array = array_values($array);
	return $count;
}


function intlog(
	int $num,
	int $base = 2
) : int {
	if ($num < 1) throw new ValueError();
	$r = 0;
	for ($comp = 1; $comp < $num; $comp *= $base) ++$r;
	if ($comp === $num) return $r;
	return $r - 1;
}


function fetch_curl($url, $options) {
	$curl_opts = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_FOLLOWLOCATION => true,
	    CURLOPT_HEADER => true
	);

	$body = $options['body'] ?? '';
	if (is_array($body)) $body = http_build_query($body);

	$method = strtoupper($options['method'] ?? 'GET');
	switch ($method) {
		case 'POST': {
			$curl_opts[CURLOPT_POST] = true;
			if ($body) $curl_opts[CURLOPT_POSTFIELDS] = $body;
			break;
		}
		case 'PUT':
		case 'DELETE': {
			$curl_opts[CURLOPT_CUSTOMREQUEST] = $method;
			if ($body) $curl_opts[CURLOPT_POSTFIELDS] = $body;
			break;
		}
		case 'GET': {
			if ($body)
				$curl_opts[CURLOPT_URL] .= (strpos($url, '?') ? '&' : '?') . $body;
			break;
		}
		default: throw new InvalidArgumentException("unknown method $method");
	}

	if (isset($options['headers'])) {
		/**
		 * 三種格式：
		 * * [['Content-Type', 'text/html'], ...]
		 * * ['Content-Type: text/html', ...]
		 * * {'Content-Type': 'text/html'}
		 */
		$req_headers = array();
		foreach ($options['headers'] as $key => $value) {
			if (is_numeric($key)) {
				if (is_string($value)) $req_headers[] = $value;
				else $req_headers[] = "$value[0]: $value[1]";
			}
			else $req_headers[] = "$key: $value";
		}
		$curl_opts[CURLOPT_HTTPHEADER] = $req_headers;
	}

	foreach ($options as $index => $value) {
		if (is_numeric($index)) $curl_opts[intval($index)] = $value;
		else if (! in_array($index, array('method', 'headers', 'body')))
			trigger_error("unsupported option $index", E_USER_NOTICE);
	}

	$ch = curl_init();
	curl_setopt_array($ch, $curl_opts);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	$errno = curl_errno($ch);
	curl_close($ch);

	$result = array('info' => $info);
	if ($errno) {
		$result['errno'] = $errno;
		$result['error'] = curl_strerror($errno);
	}
	if (! $response) return $result;

	if ($curl_opts[CURLOPT_RETURNTRANSFER]) {
		if ($curl_opts[CURLOPT_HEADER]) {
			$header_raw = substr($response, 0, $info['header_size']);
			$result['headers'] = array_map(
				fn ($h) => rtrim(preg_replace('/\r\n\s/', ' ', $h)),
				preg_split('/\r\n(?!\s)/', rtrim($header_raw))
			);
			$result['body'] = substr($response, $info['header_size']);
		}
		else $result['body'] = $response;
	}

	return $result;
}


function my_ini_set(
	string $option,
	mixed $value
) : mixed {
	switch ($option) {
		case 'error_reporting':
			return error_reporting(is_numeric($value) ? $value : constant($value));
		case 'date.timezone':
			return date_default_timezone_set($value);
		case 'session.name':
			return session_name($value);
		case 'session.save_path':
			return session_save_path($value);
		case 'output_buffering': {
			if (! $value || ob_get_level() > 1) return false;
			return ob_start(null, is_bool($value) ? 0 : $value);
		}
		case 'implicit_flush':
			return ob_implicit_flush($value);
		case 'expose_php': {
			if (ini_get('expose_php')) {
				if (! $value) header_remove('X-Powered-By');
			}
			else if ($value) header('X-Powered-By: PHP/' . PHP_VERSION);
			return null;
		}
		case 'opcache.enable': {
			/// 除了 `php.ini` 外， runtime 時（包含 `.user.ini`）僅能設為 Off ，不能設為 On 。
			if ($value) return false;
			return ini_set('opcache.enable', '0');
		}
		default:
			return ini_set($option, $value);
	}
}


function exit_text(
	string $value = '',
	int $status = 0
) {
	if (! headers_sent()) {
		if ($status) http_response_code($status);
		header('Content-Type: text/plain; charset=utf-8');
	}
	echo $value;
	exit($status < 400 ? 0 : 1);
}


function exit_json(
	mixed $value,
	int $status = 0
) {
	if (! headers_sent()) {
		if ($status) http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
	}
	echo json_encode_pretty($value);
	exit($status < 400 ? 0 : 1);
}


function my_session_start(
	array $options = array()
) : bool {
	switch (session_status()) {
		case PHP_SESSION_DISABLED:
			exit_json(array('errors' => array(
				'status' => '500',
				'title' => 'session disabled'
			)));
		case PHP_SESSION_NONE:
			if (session_start($options)) return true;
			exit_json(array('errors' => array(
				'status' => '500',
				'title' => 'Failed to start session.'
			)));
		case PHP_SESSION_ACTIVE:
			return true;
	}
}


/**
 * 設定 Cookie ，格式模仿內建的 `setcookie()`， 但使用不同的預設值。
 * 因為 PHP < 7.3 不支援 `SameSite` 屬性，故乾脆自己湊出字串然後用 header()。
 */
function my_set_cookie(
	string $name, // same as original; illegal characters throw Exception.
	string $value = '',	// same as original (`setcookie()` auto-use `rawurlencode()`)
	int $max_age = -1,  // instead of `Expires`, `Max-Age` is used here. Minus value deletes it; zero makes it live as session (aka until browser close).
	string $path = '', // auto-encoded (illegal characters in `Path` make `setcookie()` fatal error)
	string $domain = '', // same as original
	bool $secure = true, // opposite as original
	bool $httponly = true, // opposite as original
	?string $samesite = 'Lax'
) : bool {
	if (headers_sent())
		throw new RuntimeException('Headers already sent, cannot set cookie.');
	if (! preg_match('/^[\w\-\.]+$/', $name))
		throw new InvalidArgumentException("Invalid character used in cookie name.");

	$header = "Set-Cookie: $name=" . rawurlencode($value);
	if ($max_age) $header .= "; Max-Age=$max_age";
	if ($path) $header .= '; Path=' . str_replace('%2F', '/', urlencode($path));
	if ($domain) $header .= "; Domain=$domain";
	if ($secure) $header .= '; Secure';
	if ($httponly) $header .= '; HttpOnly';
	if ($samesite) $header .= "; SameSite=$samesite";
	header($header, false);
	return true;
}
