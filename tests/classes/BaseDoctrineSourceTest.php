<?php

namespace Mesour\Sources\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Mesour\Sources\DoctrineSource;
use Mesour\Sources\InvalidStateException;
use Mesour\Sources\Structures\Columns\ManyToManyColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToOneColumnStructure;
use Mesour\Sources\Structures\Columns\OneToManyColumnStructure;
use Mesour\Sources\Structures\Columns\OneToOneColumnStructure;
use Mesour\Sources\Tests\Entity\Company;
use Mesour\Sources\Tests\Entity\EmptyTable;
use Mesour\Sources\Tests\Entity\Group;
use Mesour\Sources\Tests\Entity\User;
use Mesour\Sources\Tests\Entity\UserAddress;
use Mesour\Sources\Tests\Entity\Wallet;
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

		$cache = new \Doctrine\Common\Cache\FilesystemCache(__DIR__ . '/../../tmp');
		$config = Setup::createConfiguration($isDevMode, __DIR__ . '/../../tmp', $cache);
		$config->setProxyDir(__DIR__ . '/../../tmp');
		$config->setProxyNamespace('MyProject\Proxies');

		$config->setAutoGenerateProxyClasses(true);

		$paths = [__DIR__ . '/../Entity'];

		$driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new AnnotationReader(), $paths);
		\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
		$config->setMetadataDriverImpl($driver);
		//$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
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
		$this->matchWhere($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
	}

	public function testWhereDate()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);
		$source->where('u.lastLogin > :last_login', ['last_login' => self::DATE_BIGGER]);
		$this->matchWhereDate($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
	}

	public function testEmpty()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->empty);
		$this->matchEmpty($source);
	}

	public function testFetchLastRawRows()
	{
		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);

		Assert::exception(
			function () use ($source) {
				$source->fetchLastRawRows();
			},
			InvalidStateException::class
		);

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
			Wallet::class,
		];

		$dataStructure = $source->getDataStructure();

		/** @var ManyToOneColumnStructure $group */
		$group = $dataStructure->getColumn('group');
		$group->setPattern('{name} - {type}');

		$this->assertDataStructure($source, $tableNames, User::class);
	}

	public function testReferencedData()
	{
		$selection = clone $this->user;
		$selection->select('users.*');

		$source = new DoctrineSource(User::class, self::OWN_PRIMARY_KEY, $this->user, $this->columnMapping);

		$dataStructure = $source->getDataStructure();

		/** @var ManyToManyColumnStructure $companiesColumn */
		$companiesColumn = $dataStructure->getColumn('companies');
		$companiesColumn->setPattern('{name}');

		/** @var OneToManyColumnStructure $addressesColumn */
		$addressesColumn = $dataStructure->getColumn('addresses');
		$addressesColumn->setPattern('{street}, {zip} {city}, {country}');

		/** @var ManyToOneColumnStructure $groupColumn */
		$groupColumn = $dataStructure->getColumn('group');
		$groupColumn->setPattern('{name} - {type}');

		/** @var OneToOneColumnStructure $walletColumn */
		$walletColumn = $dataStructure->getColumn('wallet');
		$walletColumn->setPattern('{amount}');

		$item = $source->fetchAll();
		Assert::equal(reset($item), $firstItem = $source->fetch());
		Assert::count(self::COLUMN_COUNT, array_keys((array) $firstItem));

		Assert::count(3, $firstItem['companies']);
		Assert::equal($this->getFirstExpectedCompany(), reset($firstItem['companies']));
		Assert::equal($this->getFirstExpectedAddress(), reset($firstItem['addresses']));
		Assert::equal($this->getFirstExpectedGroup(), $firstItem['group']);
		Assert::equal($this->getFirstExpectedWallet(), $firstItem['wallet']);
	}

	protected function getFirstExpectedCompany()
	{
		$out = parent::getFirstExpectedCompany();
		$out['verified'] = true;
		return $out;
	}

	protected function getFirstExpectedGroup()
	{
		$out = parent::getFirstExpectedGroup();
		$out['date'] = new \DateTime('2016-01-01');
		return $out;
	}

}
