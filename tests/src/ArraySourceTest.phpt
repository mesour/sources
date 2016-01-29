<?php

namespace Mesour\Sources\Tests;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../classes/DataSourceTestCase.php';
require_once __DIR__ . '/../classes/BaseArraySourceTest.php';

class ArraySourceTest extends BaseArraySourceTest
{
}

$test = new ArraySourceTest();
$test->run();