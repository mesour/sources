<?php
namespace Mesour\Sources\Tests;

use Mesour\Sources\ISource;
use Mesour\Sources\Structures\Columns\IColumnStructure;
use Tester\Assert;
use Tester\TestCase;

abstract class DataSourceTestCase extends TestCase
{

	const CHANGED_PRIMARY_KEY = 'user_id';
	const OWN_PRIMARY_KEY = 'id';
	const FULL_USER_COUNT = 20;
	const COLUMN_COUNT = 13;
	const FULL_COLUMN_COUNT = 18;
	const COLUMN_RELATION_COUNT = 13;
	const FIRST_GROUP_NAME = 'Group 1';
	const ACTIVE_COUNT = 10;
	const INACTIVE_STATUS = 0;
	const GROUPS_COUNT = 3;
	const USERS_WITH_FIRST_GROUP = 7;
	const ACTIVE_STATUS = 1;
	const DATE_BIGGER = '2014-09-01 06:27:32';
	const DATE_BIGGER_COUNT = 12;
	const CHECKERS_COUNT = 8;
	const CUSTOM_COUNT = 7;
	const CUSTOM_OR_COUNT = 3;
	const LIMIT = 5;
	const OFFSET = 2;

	protected $config = [];

	protected $configFile;

	protected $localConfigFile;

	private $pairs = [
		'1' => 'John',
		'2' => 'Peter',
		'3' => 'Claude',
		'4' => 'Stuart',
		'5' => 'Kathy',
		'6' => 'Jan',
		'7' => 'Alberta',
		'8' => 'Ada',
		'9' => 'Ethel',
		'10' => 'Ian',
		'11' => 'Francis',
		'12' => 'Erma',
		'13' => 'Kristina',
		'14' => 'Virgil',
		'15' => 'Max',
		'16' => 'Melody',
		'17' => 'Catherine',
		'18' => 'Douglas',
		'19' => 'Patti',
		'20' => 'John',
	];

	protected $baseConnection;

	/** @var DatabaseFactory */
	protected $databaseFactory;

	public function __construct()
	{
		$configFile = $this->configFile ? $this->configFile : __DIR__ . '/../config.php';
		$localConfigFile = $this->localConfigFile ? $this->localConfigFile : __DIR__ . '/../config.local.php';
		$this->config = is_file($localConfigFile) ? require_once $localConfigFile : require_once $configFile;

		$this->databaseFactory = new DatabaseFactory(
			'127.0.0.1',
			$this->config['database']['username'],
			$this->config['database']['password'],
			'mesour_sources_'
		);
		$this->baseConnection = $this->databaseFactory->create();
	}

	public function __destruct()
	{
		$this->databaseFactory->destroy($this->baseConnection);
	}

	protected function assertDataStructure(ISource $source, array $tableNames, $table = 'users')
	{
		Assert::same(self::OWN_PRIMARY_KEY, $source->getDataStructure()->getPrimaryKey());
		Assert::same($table, $source->getDataStructure()->getName());

		$expectedColumns = $this->getUserExpectedColumns();

		foreach ($expectedColumns as $name => $options) {
			$column = $source->getDataStructure()->getColumn($name);
			Assert::same($options['type'], $column->getType());
			if ($options['type'] === IColumnStructure::ENUM) {
				Assert::same($options['values'], $column->getValues());
			}
		}

		$expectedGroupColumns = $this->getGroupExpectedColumns();
		$expectedAddressColumns = $this->getAddressExpectedColumns();
		$expectedCompanyColumns = $this->getCompanyExpectedColumns();
		$userCompaniesColumns = $this->getUserCompaniesExpectedColumns();

		foreach ($tableNames as $currentTableName) {
			$tableStructure = $source->getDataStructure()->getTableStructure($currentTableName);

			$expected = [];
			if ($tableStructure->getName() === $tableNames[0]) {
				$expected = $expectedGroupColumns;
			} elseif ($tableStructure->getName() === $tableNames[1]) {
				$expected = $expectedAddressColumns;
			} elseif ($tableStructure->getName() === $tableNames[2]) {
				$expected = $expectedCompanyColumns;
			} elseif (isset($tableNames[3]) && $tableStructure->getName() === $tableNames[3]) {
				$expected = $userCompaniesColumns;
			}

			foreach ($expected as $name => $options) {
				$column = $tableStructure->getColumn($name);
				Assert::same($options['type'], $column->getType(), 'For column ' . $name);
				if ($options['type'] === IColumnStructure::ENUM) {
					Assert::same($options['values'], $column->getValues());
				}
			}
		}
	}

	protected function getUserExpectedColumns()
	{
		return [
			'id' => [
				'type' => IColumnStructure::NUMBER,
			], 'action' => [
				'type' => IColumnStructure::NUMBER,
			], 'group_id' => [
				'type' => IColumnStructure::NUMBER,
			], 'role' => [
				'type' => IColumnStructure::ENUM,
				'values' => ['admin', 'moderator'],
			], 'name' => [
				'type' => IColumnStructure::TEXT,
			], 'surname' => [
				'type' => IColumnStructure::TEXT,
			], 'email' => [
				'type' => IColumnStructure::TEXT,
			], 'last_login' => [
				'type' => IColumnStructure::DATE,
			], 'amount' => [
				'type' => IColumnStructure::NUMBER,
			], 'avatar' => [
				'type' => IColumnStructure::TEXT,
			], 'order' => [
				'type' => IColumnStructure::NUMBER,
			], 'timestamp' => [
				'type' => IColumnStructure::NUMBER,
			], 'has_pro' => [
				'type' => IColumnStructure::BOOL,
			],
		];
	}

	protected function getGroupExpectedColumns()
	{
		return [
			'id' => [
				'type' => IColumnStructure::NUMBER,
			],
			'name' => [
				'type' => IColumnStructure::TEXT,
			],
			'type' => [
				'type' => IColumnStructure::ENUM,
				'values' => ['first', 'second'],
			],
			'date' => [
				'type' => IColumnStructure::DATE,
			],
			'members' => [
				'type' => IColumnStructure::NUMBER,
			],
		];
	}

	protected function getAddressExpectedColumns()
	{
		return [
			'id' => [
				'type' => IColumnStructure::NUMBER,
			],
			'user_id' => [
				'type' => IColumnStructure::NUMBER,
			],
			'street' => [
				'type' => IColumnStructure::TEXT,
			],
			'city' => [
				'type' => IColumnStructure::TEXT,
			],
			'zip' => [
				'type' => IColumnStructure::TEXT,
			],
			'country' => [
				'type' => IColumnStructure::TEXT,
			],
		];
	}

	protected function getUserCompaniesExpectedColumns()
	{
		return [
			'company_id' => [
				'type' => IColumnStructure::NUMBER,
			],
			'user_id' => [
				'type' => IColumnStructure::NUMBER,
			],
		];
	}

	protected function getCompanyExpectedColumns()
	{
		return [
			'id' => [
				'type' => IColumnStructure::NUMBER,
			],
			'name' => [
				'type' => IColumnStructure::TEXT,
			],
			'reg_num' => [
				'type' => IColumnStructure::TEXT,
			],
			'verified' => [
				'type' => IColumnStructure::BOOL,
			],
		];
	}

	protected function matchPrimaryKey(ISource $source, $current = self::OWN_PRIMARY_KEY)
	{
		Assert::same($current, $source->getPrimaryKey());
	}

	protected function matchTotalCount(ISource $source)
	{
		Assert::same(self::FULL_USER_COUNT, $source->getTotalCount());
	}

	protected function matchLimit(ISource $source)
	{
		$source->applyLimit(self::LIMIT);
		$this->assertLimit($source);
	}

	protected function matchPairs(ISource $source)
	{
		Assert::same($this->pairs, $source->fetchPairs(self::OWN_PRIMARY_KEY, 'name'));
	}

	protected function matchOffset(ISource $source)
	{
		$source->applyLimit(self::LIMIT, self::OFFSET);
		$allData = $this->assertLimit($source);
		$firstUser = reset($allData);
		Assert::equal((string) (self::OFFSET + 1), (string) $firstUser[self::OWN_PRIMARY_KEY]);
	}

	protected function matchWhere(ISource $source, $full = self::FULL_USER_COUNT, $columns = self::COLUMN_COUNT)
	{
		$this->assertCounts($source, self::ACTIVE_COUNT, $full, $columns);
	}

	protected function matchWhereDate(ISource $source, $full = self::FULL_USER_COUNT, $columns = self::COLUMN_COUNT)
	{
		$this->assertCounts($source, self::DATE_BIGGER_COUNT, $full, $columns);
	}

	protected function matchEmpty(ISource $source)
	{
		$this->assertCounts($source, 0, 0, 0, true);
	}

	private function assertCounts(ISource $source, $activeCount, $full = self::FULL_USER_COUNT, $columns = self::COLUMN_COUNT, $fetch = false)
	{
		$itemData = $source->fetch();
		if (!$fetch) {
			Assert::count($columns, $itemData);
		} else {
			Assert::same(false, $itemData);
		}
		Assert::count($activeCount, $source->fetchAll());
		Assert::same($full, $source->getTotalCount());
		Assert::same($activeCount, $source->count());
	}

	private function assertLimit(ISource $source)
	{
		$all = $source->fetchAll();
		Assert::count(self::LIMIT, $all);
		Assert::same(self::FULL_USER_COUNT, $source->getTotalCount());
		Assert::same(self::LIMIT, $source->count());

		return $all;
	}

}
