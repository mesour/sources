<?php

namespace Mesour\Sources\Tests;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../classes/DataSourceTestCase.php';
require_once __DIR__ . '/../classes/BaseDoctrineSourceTest.php';

class DoctrineSourceTest extends BaseDoctrineSourceTest
{
}

$test = new DoctrineSourceTest();
$test->run();