<?php
require_once 'polyfill.php';

/**
 * core
 *
 * @return array(
 *    'ok' => bool,
 *    'status' => int,
 *    'status_text' => string,
 *    'header' => array,
 *    'body' => string
 *  )
 *
 * Returned value with key `header` (in singular form) is similar to
 * what native `get_headers()` with `$associative = true` would return,
 * except that all keys are in lower case.
 *
 * @link https://www.php.net/manual/en/reserved.variables.httpresponseheader.php#122362
 */
function http_request(
	string $url,
	array $wrapper = array('method' => 'GET')
) : array {
	$context = stream_context_create(array('http' => $wrapper)); // even for https, the key shall still be 'http'
	$body = file_get_contents($url, false, $context);

	foreach ($http_response_header as $line) {
	    if (str_starts_with($line, 'HTTP/1.')) {
	        $header = array($line); // this may not be the first element if there were redirection; therefore clear $header.
	        $status = intval(substr($line, 9, 3));
	        $status_text = substr($line, 13);
	        continue;
	    }
	    list($param, $value) = explode(':', $line, 2);
	    $header[strtolower($param)] = trim($value);
	}

	return array(
	    'ok' => ($status === 200),
	    'status' => $status,
	    'status_text' => $status_text,
	    'header' => $header,
	    'body' => $body
	);
}


function http_get(
	string $url,
	array $params = array(),
	array $header = array()
) : array {
	$wrapper = array(
	    'method' => 'GET',
	    'header' => $header
	);
	if (count($params)) {
	    $url .= (strpos($url, '?')) ? '&' : '?';
	    $url .= http_build_query($params);
	}
	return http_request($url, $wrapper);
}


/**
 *
 * @param $content
 *  如果是數字索引的陣列，那就當成 multipart/form-data ；
 *  如果是關聯陣列，那就當成 application/urlencoded ； *
 *  如果是字串，那就直接當內容（不改檔頭）。
 */
function http_post(
	string $url,
	/*array|string*/ $content = NULL,
	array $header = array()
) : array {
	if (is_array($content)) {
	    if (array_is_list($content)) {
	        $boundary = base64_encode(random_bytes(51));
	        $header[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;

	        $string = '';
	        foreach ($multipart as $part) {
	            $string .= "--$boundary\r\n";
	            $string .= "Content-Disposition: form-data; name=\"{$part['name']}\"";
	            if (isset($part['filename'])) $string .= "; filename=\"{$part['filename']}\"";
	            if (isset($part['type'])) $string .= "\r\nContent-Type: {$part['type']}";
	            $string .= "\r\n\r\n" . $part['value'] . "\r\n";
	        }
	        $content = "$string--$boundary--\r\n";
	    }
	    else {
	        $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
	        $content = http_build_query($content);
	    }
	}

	$wrapper = array(
	    'method' => 'POST',
	    'header' => $header
	);
	if (isset($content)) $wrapper['content'] = $content;
	return http_request($url, $wrapper);
}


function http_post_json(
	string $url,
	/*array|string*/ $json,
	array $header = array()
) {
	$header[] = 'Content-Type: application/json; charset=utf-8';
	if (gettype($json) !== 'string') $json = json_encode($json);
	return http_post($url, $json, $header);
}
