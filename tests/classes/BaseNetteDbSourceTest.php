<?php

namespace Mesour\Sources\Tests;

use Mesour\Sources\InvalidStateException;
use Mesour\Sources\NetteDbTableSource;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Database;
use Tester\Assert;

abstract class BaseNetteDbSourceTest extends DataSourceTestCase
{

	/** @var \Nette\Database\Connection */
	protected $connection;

	/** @var \Nette\Database\Context */
	protected $context;

	/** @var \Nette\Database\Table\Selection */
	protected $user;

	/** @var \Nette\Database\Table\Selection */
	protected $empty;

	protected $tableName = 'users';

	protected $columnMapping = [
		'group_name' => 'group.name',
		'group_type' => 'group.type',
	];

	public function __construct()
	{
		parent::__construct();

		$this->connection = new Database\Connection(
			$this->baseConnection->getDsn(),
			$this->databaseFactory->getUserName(),
			$this->databaseFactory->getPassword()
		);

		$cacheMemoryStorage = new MemoryStorage;

		$structure = new Database\Structure($this->connection, $cacheMemoryStorage);
		$conventions = new Database\Conventions\DiscoveredConventions($structure);
		$this->context = new Database\Context($this->connection, $structure, $conventions, $cacheMemoryStorage);

		$this->user = $this->context->table($this->tableName);
		$this->empty = $this->context->table('empty');
	}

	public function testPrimaryKey()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$this->matchPrimaryKey($source);
	}

	public function testTotalCount()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		Assert::same(self::FULL_USER_COUNT, $source->getTotalCount());
	}

	public function testFetchPairs()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$this->matchPairs($source);
	}

	public function testLimit()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$this->matchLimit($source);
	}

	public function testOffset()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$this->matchOffset($source);
	}

	public function testWhere()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$source->where('action = ?', self::ACTIVE_STATUS);
		$this->matchWhere($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
	}

	public function testWhereDate()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);
		$source->where('last_login > ?', self::DATE_BIGGER);
		$this->matchWhereDate($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
	}

	public function testEmpty()
	{
		$source = new NetteDbTableSource('empty', 'id', $this->empty, $this->context);
		$this->matchEmpty($source);
	}

	public function testFetchLastRawRows()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context);

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
			Assert::type(Database\Table\ActiveRow::class, $item);
		}
	}

	public function testLoadedDataStructure()
	{
		$source = new NetteDbTableSource($this->tableName, 'id', $this->user, $this->context, $this->columnMapping);

		$source->addTableToStructure('companies', 'id');

		$tableNames = [
			'groups',
			'user_addresses',
			'companies',
			'user_companies',
		];

		$this->assertDataStructure($source, $tableNames);
	}

	public function testReferencedData()
	{
		$selection = clone $this->user;
		$selection->select('users.*')
			->select('group.name group_name')
			->select('group.date group_date')
			->select('group.type group_type');

		$source = new NetteDbTableSource($this->tableName, 'id', $selection, $this->context, $this->columnMapping);

		$source->addTableToStructure('companies', 'id');

		$dataStructure = $source->getDataStructure();

		$dataStructure->addOneToOne('group_name', 'groups', 'name');
		$dataStructure->addOneToOne('group_type', 'groups', 'type');
		$dataStructure->addOneToOne('group_date', 'groups', 'date');

		$dataStructure->addOneToMany(
			'addresses',
			'user_addresses',
			'user_id',
			'{street}, {zip} {city}, {country}'
		);

		$dataStructure->addManyToMany(
			'companies',
			'companies',
			'company_id',
			'user_companies',
			'user_id',
			'{street}, {zip} {city}, {country}'
		);
		$item = $source->fetchAll();
		Assert::equal(reset($item), $firstItem = $source->fetch());
		Assert::count(self::FULL_COLUMN_COUNT, array_keys((array) $firstItem));

		Assert::count(3, $firstItem['companies']);
		Assert::count(1, $firstItem['addresses']);
	}

}
