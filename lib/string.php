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
) /*: object|array*/ {
    $parts = explode('.', $token);
    $result = [
        'header' => json_decode(base64url_decode($parts[0])),
        'payload' => json_decode(base64url_decode($parts[1]))
    ];
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

function hsc(
    /*mixed*/ $str
) /*: mixed*/ {
    return is_string($str) ? htmlspecialchars($str) : $str;
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

