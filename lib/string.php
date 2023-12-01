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
    $result = [
        'header' => json_decode(base64url_decode($parts[0])),
        'payload' => json_decode(base64url_decode($parts[1]))
    ];
    return $associative ? $result : (object) $result;
}

function parse_dataurl(
    string $url
) : array {
    if(preg_match('/^data:(\w+)\/([\w\.\-]+);base64,/', $url, $matches)) {
        return array(
            'type' => $matchs[0],
            'subtype' => $matches[1],
            'base64' => substr($url, strlen($matches[0]))
        );
    }
    else throw new Exception('not a data URL');
}
