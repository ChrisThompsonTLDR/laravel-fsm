<?php

// Bootstrap file for larastan/phpstan
// Maintains predictable cache configuration during static analysis

// Define a constant to indicate we're running static analysis
if (! defined('PHPSTAN_COMPOSER_INSTALL')) {
    define('PHPSTAN_COMPOSER_INSTALL', true);
}

// Set environment variables for static analysis
$_ENV['CACHE_STORE'] = 'array';
$_ENV['CACHE_DRIVER'] = 'array';

// Load composer autoloader
require_once __DIR__.'/vendor/autoload.php';
