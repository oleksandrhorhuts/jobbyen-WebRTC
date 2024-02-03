<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylorotwell@gmail.com>
 */

header('Access-Control-Allow-Origin: *');

// add any additional headers you need to support here

header('Access-Control-Allow-Headers: Origin, Content-Type');

header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');

/*header('Access-Control-Allow-Credentials', 'true');*/

header('Access-Control-Allow-Headers: content-type');

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}
require_once __DIR__.'/public/index.php';
