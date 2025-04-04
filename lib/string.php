<?php

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


function jwt_decode(
	string $token,
	?bool $associative = false
) : object|array {
	$parts = explode('.', $token);
	$result = array(
	    'header' => json_decode(base64url_decode($parts[0])),
	    'payload' => json_decode(base64url_decode($parts[1]))
	);
	return $associative ? $result : (object) $result;
}


function parse_dataurl(
	string $url
) : array {
	if (preg_match('/^data:(\w+)\/([\w\.\-]+);base64,/', $url, $matches)) {
	    return array(
	        'type' => $matchs[0],
	        'subtype' => $matches[1],
	        'base64' => substr($url, strlen($matches[0]))
	    );
	}
	else throw new Exception('not a data URL');
}


function json_encode_pretty(
	mixed $value
) : string|false {
	return preg_replace_callback(
		'/\n((?:    )+)/',
		fn ($matches) => "\n" . str_repeat("\t", strlen($matches[1]) / 4 - 1),
		json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	);
}


/**
 * Modify the given URL to which some query params are re-assigned.
 * @example
 * // returns 'foo.php?sort=date&page=3'
 * rebuild_url(['sort' => 'date'], 'foo.php?sort=name&page=3');
 */
function rebuild_url(
	array $new_params,
	string $url = ''
) : string {
	if (! $url) $url = $_SERVER['REQUEST_URI'];
	$parts = parse_url($url);
	if (! $parts) throw new InvalidArgumentException('The second argument must be a URL.');

	$pos = str_pos($url, '?');
	if ($pos !== false) $url = substr($url, 0, $pos);

	parse_str($parts['query'] ?? '', $old_params);
	$params = array_merge($old_params, $new_params);

	return $url .= '?' . http_build_query($params);
}


function json_file_read($filepath, $json_args = array(), $file_args = array()) {
	if (! is_readable($filepath)) return null;
	return json_decode(file_get_contents($filepath, ...$file_args), ...$json_args);
}

function json_file_write(
	$filepath,
	$value,
	$json_args = array(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
	$file_args = array()
) {
	return file_put_contents($filepath, json_encode($value, ...$json_args), ...$file_args);
}


function json_file_get($filepath, $key, $default = null) {
	if (! is_readable($filepath)) return $default;
	$data = json_decode(file_get_contents($filepath));
	return property_exists($data, $key) ? $data->$key : $default;
}


function json_file_set($filepath, $key, $value = null) {
	$data = is_readable($filepath)
	    ? json_decode(file_get_contents($filepath), true)
	    : array();
	if ($value === null) unset($data[$key]);
	else $data[$key] = $value;
	return file_put_contents($filepath, json_encode_pretty($data));
}
