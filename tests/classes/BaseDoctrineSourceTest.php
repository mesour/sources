<?php

namespace Mesour\Sources\Tests;


use Doctrine\DBAL\Types\StringType;
use Mesour\Sources\DoctrineSource;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Mesour\Sources\InvalidStateException;
use Mesour\Sources\Tests\Entity\Company;
use Mesour\Sources\Tests\Entity\EmptyTable;
use Mesour\Sources\Tests\Entity\Group;
use Mesour\Sources\Tests\Entity\User;
use Mesour\Sources\Tests\Entity\UserAddress;
use Tester\Assert;

abstract class BaseDoctrineSourceTest extends DataSourceTestCase
{

	/** @var \Doctrine\ORM\EntityManager */
	protected $entityManager;

	/** @var \Doctrine\ORM\QueryBuilder */
	protected $user;

	/** @var \Doctrine\ORM\QueryBuilder */
	protected $empty;

	protected $columnMapping = [
		'id' => 'u.id',
		'group_id' => 'u.groups',
		'last_login' => 'u.lastLogin',
		'group_name' => 'g.name',
		'group_type' => 'g.type',
		'group_date' => 'g.date',
	];

	public function __construct($entityDir = null)
	{
		parent::__construct();

		$isDevMode = false;

		$cache = new \Doctrine\Common\Cache\FilesystemCache(__DIR__ . '/../tmp');
		$config = Setup::createConfiguration($isDevMode, __DIR__ . '/../tmp', $cache);
		$config->setProxyDir(__DIR__ . '/../tmp');
		$config->setProxyNamespace('MyProject\Proxies');

		$config->setAutoGenerateProxyClasses(true);

		$paths = [__DIR__ . "/../Entity"];

		$driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new AnnotationReader(), $paths);
		\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
		$config->setMetadataDriverImpl($driver);
		$conn = [
			'driver' => 'mysqli',
			'host' => '127.0.0.1',
			'user' => $this->databaseFactory->getUserName(),
			'password' => $this->databaseFactory->getPassword(),
			'dbname' => $this->databaseFactory->getDatabaseName(),
		];

		$this->entityManager = EntityManager::create($conn, $config);

		$this->entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
		\Doctrine\DBAL\Types\Type::addType('enum', StringType::class);

		$this->user = $this->entityManager->createQueryBuilder()
			->select('u')
			->from(User::class, 'u');

		$this->empty = $this->entityManager->createQueryBuilder()
			->select('e')
			->from(EmptyTable::class, 'e');
	}

	public function testPrimaryKey()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchPrimaryKey($source);

		$source = new DoctrineSource(User::class, self::CHANGED_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchPrimaryKey($source, self::CHANGED_PRIMARY_KEY);
	}

	public function testTotalCount()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchTotalCount($source);
	}

	public function testFetchPairs()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchPairs($source);
	}

	public function testLimit()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchLimit($source);
	}

	public function testOffset()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$this->matchOffset($source);
	}

	public function testWhere()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$source->where('u.action = :action', ['action' => self::ACTIVE_STATUS]);
		$this->matchWhere($source, self::FULL_USER_COUNT, $columns = self::FULL_COLUMN_COUNT);
	}

	public function testWhereDate()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$source->where('u.lastLogin > :last_login', ['last_login' => self::DATE_BIGGER]);
		$this->matchWhereDate($source, self::FULL_USER_COUNT, $columns = self::FULL_COLUMN_COUNT);
	}

	public function testEmpty()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->empty);
		$this->matchEmpty($source);
	}

	public function testFetchLastRawRows()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);

		Assert::exception(function () use ($source) {
			$source->fetchLastRawRows();
		}, InvalidStateException::class);

		$source->fetchAll();

		$rawData = $source->fetchLastRawRows();

		Assert::count(self::FULL_USER_COUNT, $rawData);
		foreach ($rawData as $item) {
			Assert::type(User::class, $item);
		}
	}

	public function testLoadedDataStructure()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);

		$tableNames = [
			Group::class,
			UserAddress::class,
			Company::class,
		];

		$this->assertDataStructure($source, $tableNames, User::class);
	}

}