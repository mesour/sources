<?php

define('SRC_DIR', __DIR__ . '/../src/');

require_once __DIR__ . '/../vendor/autoload.php';

require_once SRC_DIR . 'ISource.php';
require_once SRC_DIR . 'ArraySource.php';
require_once SRC_DIR . 'NetteDbSource.php';

if (!class_exists('Tester\Assert')) {
    echo "Install Nette Tester using `composer update --dev`\n";
    exit(1);
}
@mkdir(__DIR__ . "/log");
@mkdir(__DIR__ . "/tmp");

define("TEMP_DIR", __DIR__ . "/tmp/");

Tester\Helpers::purge(TEMP_DIR);

Tester\Environment::setup();

function id($val) {
    return $val;
}