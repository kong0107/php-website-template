<?php

if (PHP_VERSION_ID < 80000) {
    function str_starts_with( // https://www.php.net/manual/zh/function.str-starts-with.php#125913
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    function str_ends_with(
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && substr($haystack, -strlen($needle)) == $needle;
    }

    function str_contains(
        string $haystack,
        string $needle
    ) : bool {
        return $needle != '' && mb_strpos($haystack, $needle) !== false;
    }
}

if (PHP_VERSION_ID < 80100) {
    function array_is_list( // https://www.php.net/manual/zh/function.array-is-list.php#126794
        array $array
    ) : bool {
        $i = 0;
        foreach($array as $k => $v) {
            if($k !== $i++) return false;
        }
        return true;
    }
}
