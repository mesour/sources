<?php

namespace Mesour\Sources\Tests;

use Mesour\Sources\ArrayHash;
use Mesour\Sources\InvalidStateException;
use Mesour\Sources\NetteDbTableSource;
use Mesour\Sources\Structures\Columns\ManyToManyColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToOneColumnStructure;
use Mesour\Sources\Structures\Columns\OneToManyColumnStructure;
use Mesour\Sources\Structures\Columns\OneToOneColumnStructure;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Database;
use Nette\Utils\DateTime;
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

		$cacheMemoryStorage = new MemoryStorage();

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
			'wallets',
			'user_companies',
		];

		$this->assertDataStructure($source, $tableNames);
	}

	public function testReferencedData()
	{
		$selection = clone $this->user;
		$selection->select('users.*');

		$source = new NetteDbTableSource($this->tableName, 'id', $selection, $this->context, $this->columnMapping);

		$dataStructure = $source->getDataStructure();

		$dataStructure->renameColumn('user_addresses', 'addresses');
		$dataStructure->renameColumn('groups', 'group');
		$dataStructure->renameColumn('wallets', 'wallet');

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
		Assert::equal($this->getFirstExpectedCompany(), $firstItem['companies'][0]);
		Assert::equal($this->getFirstExpectedAddress(), $firstItem['addresses'][0]);
		Assert::equal($this->getFirstExpectedGroup(), $firstItem['group']);
		Assert::equal($this->getFirstExpectedWallet(), $firstItem['wallet']);
	}

	protected function getFirstExpectedGroup()
	{
		$out = parent::getFirstExpectedGroup();
		$out['date'] = new DateTime('2016-01-01');
		return $out;
	}

}
