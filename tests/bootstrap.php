<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function ($className) {
    if (strpos($className, 'rest\\') === false) {
        return;
    }
    $className = str_replace('\\', '/', $className);
    $path = __DIR__ . "/$className.php";
    require $path;
});

$path = DRUPAL_ROOT . '/../.env';
if (file_exists($path)) {
    $dotenv = new Dotenv();
    $dotenv->load($path);
}

if (!isset($_ENV['COUNTRY'])) {
    $_ENV['COUNTRY'] = 'ca';
}
