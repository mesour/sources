<?php

namespace Mesour\Sources\Tests;


use Doctrine\ORM\Query\Expr\Join;
use Mesour\Sources\DoctrineSource;
use Mesour\Sources\Exception;
use Mesour\Sources\InvalidStateException;
use Mesour\Sources\Tests\Entity\User;
use Tester\Assert;
use Mesour\Sources\Tests\Entity\Groups;

abstract class BaseDoctrineSourceTest extends DataSourceTestCase
{

	/** @var \Doctrine\ORM\EntityManager */
	protected $entityManager;

	/** @var \Doctrine\ORM\QueryBuilder */
	protected $user;

	/** @var \Doctrine\ORM\QueryBuilder */
	protected $empty;

	protected $primaryKey = 'userId';

	protected $columnMapping = [
		'userId' => 'u.userId',
		'group_id' => 'u.groups',
		'last_login' => 'u.lastLogin',
		'groupName' => 'g.name',
		'groupType' => 'g.type',
	];

	public function __construct($entityDir = null)
	{
		parent::__construct();

		if (!$entityDir) {
			// settings for next required file
			$conn = [
				'driver' => 'pdo_mysql',
				'user' => $this->databaseFactory->getUserName(),
				'password' => $this->databaseFactory->getPassword(),
				'dbname' => $this->databaseFactory->getDatabaseName(),
			];
			$this->entityManager = require_once __DIR__ . '/../../demo/bootstrap.php';

			$this->user = $this->entityManager->createQueryBuilder()
				->select('u')
				->from('Mesour\Sources\Tests\Entity\User', 'u');
			$this->empty = $this->entityManager->createQueryBuilder()
				->select('e')
				->from('Mesour\Sources\Tests\Entity\emptyTable', 'e');
		}
	}

	public function testPrimaryKey()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$this->matchPrimaryKey($source);
	}

	public function testTotalCount()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$this->matchTotalCount($source);
	}

	public function testFetchPairs()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$this->matchPairs($source);
	}

	public function testLimit()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$this->matchLimit($source);
	}

	public function testOffset()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$this->matchOffset($source);
	}

	public function testWhere()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$source->where('u.action = :action', ['action' => self::ACTIVE_STATUS]);
		$this->matchWhere($source);
	}

	public function testWhereDate()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);
		$source->where('u.lastLogin > :last_login', ['last_login' => self::DATE_BIGGER]);
		$this->matchWhereDate($source);
	}

	public function testEmpty()
	{
		$source = new DoctrineSource($this->empty);
		$source->setPrimaryKey($this->primaryKey);
		$this->matchEmpty($source);
	}

	public function testReference()
	{
		$queryBuilder = clone $this->user;
		$queryBuilder->addSelect('g.name groupName')
			->addSelect('g.type groupType')
			->join(Groups::class, 'g', Join::WITH, 'u.groupId = g.id');

		$source = new DoctrineSource($queryBuilder, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);

		$firstRow = $source->fetch();
		Assert::count(self::COLUMN_RELATION_COUNT, $firstRow);
		Assert::same(self::FIRST_GROUP_NAME, $firstRow['groupName']);

		Assert::same(false, $source->hasReference(Groups::class));

		$source->setReference('groupName', Groups::class, 'name');
		$source->setReference('groupType', Groups::class, 'type');

		Assert::same(true, $source->hasReference(Groups::class));

		$related = $source->getReferencedSource(Groups::class);

		Assert::type(DoctrineSource::class, $related);
		Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
		Assert::same(count($source->fetch()), self::COLUMN_RELATION_COUNT);

		Assert::same([
			'groupName' => [
				'table' => Groups::class,
				'column' => 'name',
				'primary' => 'id',
			],
			'groupType' => [
				'table' => Groups::class,
				'column' => 'type',
				'primary' => 'id',
			],
		], $source->getReferenceSettings());

		Assert::same([Groups::class => 'id'], $source->getReferencedTables());

		$source->where('g.name = :groupName', [
			'groupName' => 'Group 1',
		]);

		Assert::count(self::USERS_WITH_FIRST_GROUP, $source->fetchAll());
	}

	public function testFetchLastRawRows()
	{
		$source = new DoctrineSource($this->user, $this->columnMapping);
		$source->setPrimaryKey($this->primaryKey);

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

}