<?php
/**
 * Polyfill to use some features in PHP 8.5.
 *
 * @see https://www.php.net/manual/en/migration85.php
 *
 * This file has implemented:
 * *
 *
 */
if (PHP_VERSION_ID < 80500) {

if (!function_exists('get_error_handler')) {
function noop_error_handler() {}
/**
 * Gets the user-defined error handler function
 *
 * @return ?callable
 */
function get_error_handler() {
	$handler = set_error_handler('noop_error_handler');
	restore_error_handler();
	return $handler;
}
} // function get_error_handler


if (!function_exists('get_exception_handler')) {
function noop_exception_handler() {}
/**
 * Gets the user-defined exception handler function
 *
 * @return ?callable
 */
function get_exception_handler(): ?callable {
	$handler = set_exception_handler('noop_exception_handler');
	restore_exception_handler();
	return $handler;
}
} // function get_exception_handler


if (!function_exists('array_first')) {
/**
 * Gets the first value of an array
 *
 * @param array $array
 *
 * @return mixed
 */
function array_first($array) {
	$key = array_key_first($array);
	return is_null($key) ? null : $array[$key];
}
} // function array_first


if (!function_exists('array_last')) {
/**
 * Gets the last value of an array
 *
 * @param array $array
 *
 * @return mixed
 */
function array_last($array) {
	$key = array_key_last($array);
	return is_null($key) ? null : $array[$key];
}
} // function array_last


} // if (PHP_VERSION_ID < 80500)
