<?php

if (PHP_VERSION_ID < 80000)
    require_once __DIR__ . '/polyfill80.php';

if (PHP_VERSION_ID < 80100)
    require_once __DIR__ . '/polyfill81.php';

if (PHP_VERSION_ID < 80200)
    require_once __DIR__ . '/polyfill82.php';

if (PHP_VERSION_ID < 80300)
    require_once __DIR__ . '/polyfill83.php';

if (PHP_VERSION_ID < 80400)
    require_once __DIR__ . '/polyfill84.php';
