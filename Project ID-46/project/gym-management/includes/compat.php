<?php
// ==========================================================
// includes/compat.php
// PHP 5.4 COMPATIBILITY LAYER
//
// Your college lab runs PHP 5.4.31 (UwAmp), but some of the
// standard PHP functions used in this project were only added
// in later versions. This file provides safe replacements —
// each one only gets defined IF the real native function is
// missing, so this file does nothing at all on a modern PHP
// version (like XAMPP on your personal PC, likely PHP 7+/8+).
// You can safely include this everywhere without any risk of
// conflicting with a newer PHP's built-in versions.
//
// Included once, at the very top of includes/config.php, so
// every single page in the project gets these automatically.
// ==========================================================

// ---- password_hash() / password_verify() polyfill ----
// Native since PHP 5.5.0. On 5.4 we implement the same bcrypt
// behavior directly with crypt() + CRYPT_BLOWFISH, which IS
// available since PHP 5.3.7 — so hashes produced here are in
// the exact same $2y$ format a real password_hash() produces,
// and are fully compatible if this project is later moved to
// a modern PHP version.
if (!defined('PASSWORD_DEFAULT')) {
    define('PASSWORD_DEFAULT', '2y');
}

if (!function_exists('password_hash')) {
    function password_hash($password, $algo, $options = array()) {
        $cost = isset($options['cost']) ? $options['cost'] : 10;

        // Build a 22-character salt from the Blowfish-safe alphabet
        $salt_chars = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $salt = '';
        for ($i = 0; $i < 22; $i++) {
            $salt .= $salt_chars[mt_rand(0, 63)];
        }

        $cost_str = str_pad((string)$cost, 2, '0', STR_PAD_LEFT);
        $hash = crypt($password, '$2y$' . $cost_str . '$' . $salt);

        // crypt() returns a string shorter than 13 chars on failure
        if (!$hash || strlen($hash) < 13) {
            return false;
        }
        return $hash;
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        $test = crypt($password, $hash);
        if (!is_string($test) || strlen($test) !== strlen($hash) || strlen($test) < 1) {
            return false;
        }
        // Constant-time comparison to avoid timing attacks
        $status = 0;
        for ($i = 0; $i < strlen($test); $i++) {
            $status |= (ord($test[$i]) ^ ord($hash[$i]));
        }
        return $status === 0;
    }
}

// ---- array_column() polyfill ----
// Native since PHP 5.5.0.
if (!function_exists('array_column')) {
    function array_column($array, $column_key, $index_key = null) {
        $result = array();
        foreach ($array as $row) {
            if (!isset($row[$column_key])) { continue; }
            if ($index_key !== null && isset($row[$index_key])) {
                $result[$row[$index_key]] = $row[$column_key];
            } else {
                $result[] = $row[$column_key];
            }
        }
        return $result;
    }
}

// ---- mysqli_stmt_bind_param() with a dynamic/variable argument
// count. Native PHP can do this with the "..." spread operator,
// but that syntax itself requires PHP 5.6+ and would cause a
// PARSE ERROR (the whole file fails to load) on PHP 5.4 — so
// this has to be a real function, not spread syntax.
// bind_param requires its value arguments to be passed BY
// REFERENCE, which is why this uses variable-variables ($$name)
// instead of a plain array — that's the standard PHP 5.3/5.4-era
// workaround for building a by-reference argument list at runtime.
if (!function_exists('gp_bind_param_array')) {
    function gp_bind_param_array($stmt, $types, $params) {
        $args = array();
        $args[] = $stmt;
        $args[] = $types;
        $refs = array();
        foreach ($params as $i => $value) {
            $varname = 'gp_bind_' . $i;
            $$varname = $value;
            $refs[$i] = &$$varname;
        }
        foreach ($refs as $i => &$ref) {
            $args[] = &$ref;
        }
        return call_user_func_array('mysqli_stmt_bind_param', $args);
    }
}
?>
