<?php

spl_autoload_register(function ($class) {
    $prefix = 'Ksfraser\\DataIO\\';
    $base_dir = __DIR__ . '/src/Ksfraser/DataIO/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

if (!defined('TB_PREF')) {
    define('TB_PREF', '');
}

if (!function_exists('db_escape')) {
    function db_escape($val) {
        return "'" . addslashes($val) . "'";
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($key, $val, $url = '') {
        return $url . '?' . $key . '=' . urlencode($val);
    }
}