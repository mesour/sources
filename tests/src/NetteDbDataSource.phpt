<?php

namespace Mesour\Sources\Tests;

use Nette\Database;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../classes/DataSourceTestCase.php';
require_once __DIR__ . '/../classes/BaseNetteDbDataSource.php';

class NetteDbSourceTest extends BaseNetteDbSourceTest
{
}

$test = new NetteDbSourceTest();
$test->run();