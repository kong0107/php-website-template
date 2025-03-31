<?php
/**
 * Polyfill to use functions new in PHP 8.0
 *
 * @see https://www.php.net/releases/8.0/
 */
if (PHP_VERSION_ID < 80000) {

if (! interface_exists('Stringable', false)) {
interface Stringable {
	/**
	 * @return string
	 */
	public function __toString();
}
} // interface Stringable


if (! function_exists('str_contains')) {
/**
 * Determine if a string contains a given substring
 *
 * Performs a case-sensitive check indicating if `needle` is contained in `haystack`.
 *
 * @param string $haystack The string to search in.
 * @param string $needle The substring to search for in the `haystack`.
 *
 * @return bool Returns `true` if `needle` is in `haystack`, `false` otherwise.
 */
function str_contains($haystack, $needle) {
	return $needle !== '' && strpos($haystack, $needle) !== false;
	/**
	 * Check whether `needle` is empty is not only for efficiency.
	 * `strpos()` does not support empty string to be `needle` until 8.0.0.
	 */
}
} // function str_contains


if (! function_exists('str_starts_with')) {
/**
 * Checks if a string starts with a given substring
 *
 * Performs a case-sensitive check indicating if `haystack` begins with `needle`.
 *
 * @param string $haystack The string to search in.
 * @param string $needle The substring to search for in the `haystack`.
 *
 * @return bool Returns `true` if `haystack` begins with `needle`, `false` otherwise.
 */
function str_starts_with($haystack, $needle) {
	return strncmp($haystack, $needle, strlen($needle)) === 0;
}
} // function str_starts_with


if (! function_exists('str_ends_with')) {
/**
 * Checks if a string ends with a given substring
 *
 * Performs a case-sensitive check indicating if `haystack` ends with `needle`.
 *
 * @param string $haystack The string to search in.
 * @param string $needle The substring to search for in the `haystack`.
 *
 * @return bool Returns `true` if `haystack` ends with `needle`, `false` otherwise.
 */
function str_ends_with($haystack, $needle) {
	if ($haystack === $needle) return true;
	$length = strlen($needle);
	if (strlen($haystack) <= $length) return false;
	return substr_compare($haystack, $needle, - $length) === 0;
	/**
	 * For `substr_compare($haystack, $needle, $offset, $length, ...)`:
	 * > If `offset` is equal to (prior to PHP 7.2.18, 7.3.5) or greater than the length of `haystack`,
	 * > or the `length` is set and is less than 0,
	 * > `substr_compare()` prints a warning and returns `false`.
	 */
}
} // function str_ends_with


if (! function_exists('fdiv')) {
/**
 * Divides two numbers, according to IEEE 754
 *
 * Returns the floating point result of dividing the `num1` by the `num2`.
 * If the `num2` is zero, then one of `INF`, -`INF`, or `NAN` will be returned.
 *
 * Note that in comparisons, `NAN` will never be equal (==) or identical (===)
 * to any value, including itself.
 *
 * @param float $num1 The dividend (numerator)
 * @param float $num2 The divisor
 *
 * @return float The floating point result of `num1`/`num2`
 */
function fdiv($num1, $num2) {
	if ($num2) return $num1 / $num2;
	if ($num1 > 0) return INF;
	if ($num1 < 0) return -INF;
	return NAN;
}
} // function fdiv


if (! function_exists('get_debug_type')) {
/**
 * Gets the type name of a variable in a way that is suitable for debugging
 *
 * Returns the resolved name of the PHP variable `value`.
 * This function will resolve
 * 	objects to their class name,
 *  resources to their resource type name, and
 * 	scalar values to their common name
 * as would be used in type declarations.
 *
 * This function differs from `gettype()` in that
 * it returns type names that are more consistent with actual usage,
 * rather than those present for historical reasons.
 *
 * @param mixed $value The variable being type checked.
 *
 * @return string
 */
function get_debug_type($value) {
	if (is_array($value)) return 'array';
	if (is_bool($value)) return 'bool';
	if (is_float($value)) return 'float';
	if (is_int($value)) return 'int';
	if (is_null($value)) return 'null';
	if (is_string($value)) return 'string';
	if ($value instanceof __PHP_Incomplete_Class) return '__PHP_Incomplete_Class';
	if (is_object($value)) {
		$class = get_class($value);
		if (strpos($class, '@') === false) return $class;

		$parent = get_parent_class($class);
		if (! $parent) {
			$array = class_implements($class);
			$parent = empty($array) ? 'class' : $array[0];
		}
		return "$parent@anonymous";
	}
	$type = get_resource_type($value);
	if ($type === 'Unknown') $type = 'closed';
	return "resource ($type)";
}
} // function get_debug_type


if (! function_exists('get_resource_id')) {
/**
 * Returns an integer identifier for the given resource
 *
 * This function provides a type-safe way for generating the integer identifier for a resource.
 *
 * @param resource $resource The evaluated resource handle.
 *
 * @return int The int identifier for the given resource.
 * 	This function is essentially an int cast of resource to make it easier to retrieve the resource ID.
 */
function get_resource_id($resource) {
	return (int) $resource;
}
} // function get_resource_id


if (! function_exists('preg_last_error_msg')) {
/**
 * Returns the error message of the last PCRE regex execution
 *
 * @return string Returns the error message on success, or "No error" if no error has occurred.
 */
function preg_last_error_msg() {
	switch (preg_last_error()) {
		case PREG_NO_ERROR: return 'No error';
		case PREG_INTERNAL_ERROR: return 'Internal error';
		case PREG_BACKTRACK_LIMIT_ERROR: return 'Backtrack limit exhausted';
		case PREG_RECURSION_LIMIT_ERROR: return 'Recursion limit exhausted';
		case PREG_BAD_UTF8_ERROR: return 'Malformed UTF-8 characters, possibly incorrectly encoded';
		case PREG_BAD_UTF8_OFFSET_ERROR: return 'The offset did not correspond to the beginning of a valid UTF-8 code point';
		case PREG_JIT_STACKLIMIT_ERROR: return 'JIT stack limit exhausted';
		default:
			throw new UnexpectedValueException('Unknown PREG error');
	}
}
} // function preg_last_error_msg

} // if (PHP_VERSION_ID < 80000)
