<?php

require_once 'autoload.php';

// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;

$paths = array(__DIR__ . "/../tests/Entity");

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;

$config = Setup::createConfiguration($isDevMode);

$driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new AnnotationReader(), $paths);
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
$config->setMetadataDriverImpl($driver);

// or if you prefer yaml or XML
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'root',
    'dbname'   => 'demo.mesour.com',
);

// obtaining the entity manager
return EntityManager::create($conn, $config);