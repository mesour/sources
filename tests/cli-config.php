<?php

// replace with mechanism to retrieve EntityManager in your app
$entityManager = require_once __DIR__ . '/../demo/bootstrap.php';

$entityManager->getConfiguration()->setMetadataDriverImpl(
    new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
        $entityManager->getConnection()->getSchemaManager()
    )
);

$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
$cmf->setEntityManager($entityManager);
$metadata = $cmf->getAllMetadata();

// GENERATE PHP ENTITIES!
$entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();
$entityGenerator->setGenerateAnnotations(true);
$entityGenerator->setGenerateStubMethods(true);
$entityGenerator->setRegenerateEntityIfExists(false);
$entityGenerator->setUpdateEntityIfExists(true);
$entityGenerator->setUpdateEntityIfExists(true);
$entityGenerator->generate($metadata, __dir__. '/Entity');
