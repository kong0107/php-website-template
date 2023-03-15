<?php

// https://www.php.net/manual/zh/function.array-is-list.php#126794
if(!function_exists('array_is_list')) { // PHP 8 >= 8.1.0
    function array_is_list(
        array $array
    ) : bool {
        $i = 0;
        foreach($array as $k => $v) {
            if($k !== $i++) return false;
        }
        return true;
    }
}

// https://www.php.net/manual/zh/function.str-starts-with.php#125913
if(!function_exists('str_starts_with')) { // PHP 8
    function str_starts_with(
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if(!function_exists('str_ends_with')) { // PHP 8
    function str_ends_with(
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && substr($haystack, -strlen($needle)) == $needle;
    }
}
if(!function_exists('str_contains')) { // PHP 8
    function str_contains(
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && mb_strpos($haystack, $needle) !== false;
    }
}