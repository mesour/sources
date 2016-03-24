<?php

define('SRC_DIR', __DIR__ . '/../src/');
define('DISABLE_AUTOLOAD', true);

@mkdir(__DIR__ . "/log");
@mkdir(__DIR__ . "/../tmp");

require_once __DIR__ . '/../vendor/autoload.php';

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/../src');
$loader->addDirectory(__DIR__ . '/classes');
$loader->addDirectory(__DIR__ . '/Entity');
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(__DIR__ . '/../tmp'));
$loader->register();

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

define("TEMP_DIR", __DIR__ . "/../tmp/");

Tester\Helpers::purge(TEMP_DIR);

Tester\Environment::setup();