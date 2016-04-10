<?php

namespace Mesour\Sources\Tests;

use Mesour\ArrayManage\Searcher\Condition;
use Mesour\Sources\ArrayHash;
use Mesour\Sources\ArraySource;
use Mesour\Sources\InvalidStateException;
use Tester\Assert;

abstract class BaseArraySourceTest extends DataSourceTestCase
{

	static public $user = [
		['id' => 1, 'action' => '0', 'group_id' => 1, 'wallet_id' => 1, 'name' => 'John', 'surname' => 'Doe', 'email' => 'john.doe@test.xx', 'last_login' => null, 'amount' => '1561.456542', 'avatar' => '/avatar/01.png', 'order' => '100', 'timestamp' => '1418255325', 'role' => 'admin', 'has_pro' => 1],
		['id' => 2, 'action' => '1', 'group_id' => 2, 'wallet_id' => 2, 'name' => 'Peter', 'surname' => 'Larson', 'email' => 'peter.larson@test.xx', 'last_login' => '2014-09-09 13:37:32', 'amount' => '15220.654', 'avatar' => '/avatar/02.png', 'order' => '160', 'timestamp' => '1418255330', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 3, 'action' => '1', 'group_id' => 2, 'wallet_id' => null, 'name' => 'Claude', 'surname' => 'Graves', 'email' => 'claude.graves@test.xx', 'last_login' => '2014-09-02 14:17:32', 'amount' => '9876.465498', 'avatar' => '/avatar/03.png', 'order' => '180', 'timestamp' => '1418255311', 'role' => 'admin', 'has_pro' => 0],
		['id' => 4, 'action' => '0', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Stuart', 'surname' => 'Norman', 'email' => 'stuart.norman@test.xx', 'last_login' => '2014-09-09 18:39:18', 'amount' => '98766.2131', 'avatar' => '/avatar/04.png', 'order' => '120', 'timestamp' => '1418255328', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 5, 'action' => '1', 'group_id' => 1, 'wallet_id' => null, 'name' => 'Kathy', 'surname' => 'Arnold', 'email' => 'kathy.arnold@test.xx', 'last_login' => '2014-09-07 10:24:07', 'amount' => '456.987', 'avatar' => '/avatar/05.png', 'order' => '140', 'timestamp' => '1418155313', 'role' => 'admin', 'has_pro' => 0],
		['id' => 6, 'action' => '0', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Jan', 'surname' => 'Wilson', 'email' => 'jan.wilson@test.xx', 'last_login' => '2014-09-03 13:15:22', 'amount' => '123', 'avatar' => '/avatar/06.png', 'order' => '150', 'timestamp' => '1418255318', 'role' => 'moderator', 'has_pro' => 1],
		['id' => 7, 'action' => '0', 'group_id' => 1, 'wallet_id' => null, 'name' => 'Alberta', 'surname' => 'Erickson', 'email' => 'alberta.erickson@test.xx', 'last_login' => '2014-08-06 13:37:17', 'amount' => '98753.654', 'avatar' => '/avatar/07.png', 'order' => '110', 'timestamp' => '1418255327', 'role' => 'moderator', 'has_pro' => 1],
		['id' => 8, 'action' => '1', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Ada', 'surname' => 'Wells', 'email' => 'ada.wells@test.xx', 'last_login' => '2014-08-12 11:25:16', 'amount' => '852.3654', 'avatar' => '/avatar/08.png', 'order' => '70', 'timestamp' => '1418255332', 'role' => 'admin', 'has_pro' => 0],
		['id' => 9, 'action' => '0', 'group_id' => 2, 'wallet_id' => null, 'name' => 'Ethel', 'surname' => 'Figueroa', 'email' => 'ethel.figueroa@test.xx', 'last_login' => '2014-09-05 10:23:26', 'amount' => '45695.986', 'avatar' => '/avatar/09.png', 'order' => '20', 'timestamp' => '1417255305', 'role' => 'admin', 'has_pro' => 0],
		['id' => 10, 'action' => '1', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Ian', 'surname' => 'Goodwin', 'email' => 'ian.goodwin@test.xx', 'last_login' => '2014-09-04 12:26:19', 'amount' => '1236.9852', 'avatar' => '/avatar/10.png', 'order' => '130', 'timestamp' => '1418255331', 'role' => 'moderator', 'has_pro' => 1],
		['id' => 11, 'action' => '1', 'group_id' => 2, 'wallet_id' => null, 'name' => 'Francis', 'surname' => 'Hayes', 'email' => 'francis.hayes@test.xx', 'last_login' => '2014-09-03 10:16:17', 'amount' => '5498.345', 'avatar' => '/avatar/11.png', 'order' => '0', 'timestamp' => '1417255293', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 12, 'action' => '0', 'group_id' => 1, 'wallet_id' => null, 'name' => 'Erma', 'surname' => 'Burns', 'email' => 'erma.burns@test.xx', 'last_login' => '2014-07-02 15:42:15', 'amount' => '63287.9852', 'avatar' => '/avatar/12.png', 'order' => '60', 'timestamp' => '1418255316', 'role' => 'moderator', 'has_pro' => 1],
		['id' => 13, 'action' => '1', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Kristina', 'surname' => 'Jenkins', 'email' => 'kristina.jenkins@test.xx', 'last_login' => '2014-08-20 14:39:43', 'amount' => '74523.96549', 'avatar' => '/avatar/13.png', 'order' => '40', 'timestamp' => '1418255334', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 14, 'action' => '0', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Virgil', 'surname' => 'Hunt', 'email' => 'virgil.hunt@test.xx', 'last_login' => '2014-08-12 16:09:38', 'amount' => '65654.6549', 'avatar' => '/avatar/14.png', 'order' => '30', 'timestamp' => '1418255276', 'role' => 'admin', 'has_pro' => 1],
		['id' => 15, 'action' => '1', 'group_id' => 1, 'wallet_id' => null, 'name' => 'Max', 'surname' => 'Martin', 'email' => 'max.martin@test.xx', 'last_login' => '2014-09-01 12:14:20', 'amount' => '541236.5495', 'avatar' => '/avatar/15.png', 'order' => '170', 'timestamp' => '1418255317', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 16, 'action' => '1', 'group_id' => 2, 'wallet_id' => null, 'name' => 'Melody', 'surname' => 'Manning', 'email' => 'melody.manning@test.xx', 'last_login' => '2014-09-02 12:26:20', 'amount' => '9871.216', 'avatar' => '/avatar/16.png', 'order' => '50', 'timestamp' => '1418255281', 'role' => 'admin', 'has_pro' => 0],
		['id' => 17, 'action' => '1', 'group_id' => 3, 'wallet_id' => null, 'name' => 'Catherine', 'surname' => 'Todd', 'email' => 'catherine.todd@test.xx', 'last_login' => '2014-06-11 15:14:39', 'amount' => '100.2', 'avatar' => '/avatar/17.png', 'order' => '10', 'timestamp' => '1416255313', 'role' => 'moderator', 'has_pro' => 0],
		['id' => 18, 'action' => '0', 'group_id' => 1, 'wallet_id' => null, 'name' => 'Douglas', 'surname' => 'Stanley', 'email' => 'douglas.stanley@test.xx', 'last_login' => '2014-04-16 15:22:18', 'amount' => '900', 'avatar' => '/avatar/18.png', 'order' => '90', 'timestamp' => '1416255332', 'role' => 'admin', 'has_pro' => 1],
		['id' => 19, 'action' => '0', 'group_id' => 2, 'wallet_id' => null, 'name' => 'Patti', 'surname' => 'Diaz', 'email' => 'patti.diaz@test.xx', 'last_login' => '2014-09-11 12:17:16', 'amount' => '1500', 'avatar' => '/avatar/19.png', 'order' => '80', 'timestamp' => '1418255275', 'role' => 'admin', 'has_pro' => 0],
		['id' => 20, 'action' => '0', 'group_id' => 1, 'wallet_id' => null, 'name' => 'John', 'surname' => 'Petterson', 'email' => 'john.petterson@test.xx', 'last_login' => '2014-10-10 10:10:10', 'amount' => '2500', 'avatar' => '/avatar/20.png', 'order' => '190', 'timestamp' => '1418255275', 'role' => 'moderator', 'has_pro' => 0],
	];

	protected $relations = [
		'groups' => [
			['id' => 1, 'name' => 'Group 1', 'type' => 'first', 'date' => '2016-01-01 00:00:00', 'members' => 7],
			['id' => 2, 'name' => 'Group 2', 'type' => 'second', 'date' => '2016-03-05 00:00:00', 'members' => 6],
			['id' => 3, 'name' => 'Group 3', 'type' => 'second', 'date' => '2016-05-09 00:00:00', 'members' => 7],
		],
		'user_addresses' => [
			['id' => 10, 'user_id' => 1, 'street' => 'Test 1', 'city' => 'Hehehov', 'zip' => '12345', 'country' => 'CZ'],
		],
		'wallets' => [
			['id' => 1, 'user_id' => 1, 'amount' => 153.85, 'currency' => 'EUR'],
			['id' => 2, 'user_id' => 3, 'amount' => 0.85, 'currency' => 'CZK'],
		],
		'companies' => [
			['id' => 1, 'name' => 'General Motors', 'reg_num' => '123456', 'verified' => 0],
			['id' => 2, 'name' => 'Google', 'reg_num' => '789123', 'verified' => 1],
			['id' => 3, 'name' => 'Allianz', 'reg_num' => '456789', 'verified' => 0],
			['id' => 4, 'name' => 'Ford', 'reg_num' => '987654', 'verified' => 0],
			['id' => 5, 'name' => 'Foxconn', 'reg_num' => '654321', 'verified' => 0],
			['id' => 6, 'name' => 'Verizon', 'reg_num' => '357654', 'verified' => 1],
			['id' => 7, 'name' => 'Lukoil', 'reg_num' => '236846', 'verified' => 1],
			['id' => 8, 'name' => 'Honda', 'reg_num' => '982154', 'verified' => 0],
		],
		'user_companies' => [
			['user_id' => '1', 'company_id' => '2'],
			['user_id' => '1', 'company_id' => '3'],
			['user_id' => '1', 'company_id' => '5'],
			['user_id' => '2', 'company_id' => '5'],
			['user_id' => '4', 'company_id' => '5'],
			['user_id' => '5', 'company_id' => '5'],
			['user_id' => '5', 'company_id' => '6'],
			['user_id' => '8', 'company_id' => '1'],
			['user_id' => '8', 'company_id' => '5'],
			['user_id' => '8', 'company_id' => '8'],
			['user_id' => '9', 'company_id' => '7'],
			['user_id' => '10', 'company_id' => '8'],
			['user_id' => '12', 'company_id' => '6'],
			['user_id' => '13', 'company_id' => '6'],
			['user_id' => '15', 'company_id' => '2'],
			['user_id' => '15', 'company_id' => '8'],
			['user_id' => '17', 'company_id' => '3'],
			['user_id' => '19', 'company_id' => '2'],
			['user_id' => '19', 'company_id' => '6'],
			['user_id' => '20', 'company_id' => '2'],
		],
	];

	public function testReferencedData()
	{
		$source = $this->createArraySourceWithDataStructure();

		$item = $source->fetchAll();
		Assert::equal(reset($item), $firstItem = $source->fetch());
		Assert::count(self::COLUMN_COUNT, array_keys((array) $firstItem));

		Assert::count(3, $firstItem['companies']);
		Assert::equal($this->getFirstExpectedCompany(), reset($firstItem['companies']));
		Assert::equal($this->getFirstExpectedAddress(), reset($firstItem['addresses']));
		Assert::equal($this->getFirstExpectedGroup(), $firstItem['group']);
		Assert::equal($this->getFirstExpectedWallet(), $firstItem['wallet']);
	}

	public function testPrimaryKey()
	{
		$source = new ArraySource('users', 'id', self::$user);
		$this->matchPrimaryKey($source);
	}

	public function testTotalCount()
	{
		$source = new ArraySource('users', 'id', self::$user);
		$this->matchTotalCount($source);
	}

	public function testFetch()
	{
		$source = new ArraySource('users', 'id', self::$user);
		Assert::equal($source->fetch(), ArrayHash::from(reset(self::$user)));
	}

	public function testFetchPairs()
	{
		$source = new ArraySource('users', 'id', self::$user);

		$this->matchPairs($source);
	}

	public function testLimit()
	{
		$source = new ArraySource('users', 'id', self::$user);
		$this->matchLimit($source);
	}

	public function testOffset()
	{
		$source = new ArraySource('users', 'id', self::$user);
		$this->matchOffset($source);
	}

	public function testWhere()
	{
		$source = $this->createArraySourceWithDataStructure();
		$source->where('action', self::ACTIVE_STATUS, Condition::EQUAL);
		$this->matchWhere($source);
	}

	public function testWhereDate()
	{
		$source = $this->createArraySourceWithDataStructure();

		$source->where('last_login', self::DATE_BIGGER, Condition::BIGGER);
		$this->matchWhereDate($source);
	}

	public function testEmpty()
	{
		$source = new ArraySource('empty', 'id', []);
		$this->matchEmpty($source);
	}

	public function testFetchLastRawRows()
	{
		$source = $this->createArraySourceWithDataStructure();

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
			Assert::type(ArrayHash::class, $item);
		}
	}

	public function testDataStructure()
	{
		$source = $this->createArraySourceWithDataStructure();

		$tableNames = [
			'groups',
			'user_addresses',
			'companies',
			'wallets',
			'user_companies',
		];

		$this->assertDataStructure($source, $tableNames);
	}

	protected function createArraySourceWithDataStructure()
	{
		$source = new ArraySource('users', self::OWN_PRIMARY_KEY, self::$user, $this->relations);

		$dataStructure = $source->getDataStructure();
		$dataStructure->addNumber('id');
		$dataStructure->addNumber('action');
		$dataStructure->addNumber('group_id');
		$dataStructure->addEnum('role')
			->addValue('admin')
			->addValue('moderator');
		$dataStructure->addText('name');
		$dataStructure->addText('surname');
		$dataStructure->addText('email');
		$dataStructure->addDate('last_login');
		$dataStructure->addNumber('amount');
		$dataStructure->addText('avatar');
		$dataStructure->addNumber('order');
		$dataStructure->addNumber('timestamp');
		$dataStructure->addBool('has_pro');

		$groupsStructure = $dataStructure->getOrCreateTableStructure('groups', 'id');
		$groupsStructure->addNumber('id');
		$groupsStructure->addText('name');
		$groupsStructure->addEnum('type')
			->addValue('first')
			->addValue('second');
		$groupsStructure->addDate('date');
		$groupsStructure->addNumber('members');

		$addressesStructure = $dataStructure->getOrCreateTableStructure('user_addresses', 'id');
		$addressesStructure->addNumber('id');
		$addressesStructure->addNumber('user_id');
		$addressesStructure->addText('street');
		$addressesStructure->addText('city');
		$addressesStructure->addText('zip');
		$addressesStructure->addText('country');

		$companiesStructure = $dataStructure->getOrCreateTableStructure('companies', 'id');
		$companiesStructure->addNumber('id');
		$companiesStructure->addText('name');
		$companiesStructure->addText('reg_num');
		$companiesStructure->addBool('verified');

		$userCompaniesStructure = $dataStructure->getOrCreateTableStructure('user_companies', 'company_id');
		$userCompaniesStructure->addNumber('company_id');
		$userCompaniesStructure->addNumber('user_id');

		$walletStructure = $dataStructure->getOrCreateTableStructure('wallets', 'id');
		$walletStructure->addNumber('id');
		$walletStructure->addNumber('user_id');
		$walletStructure->addNumber('amount');
		$walletStructure->addEnum('currency')
			->addValue('CZK')
			->addValue('EUR');

		$dataStructure->addOneToOne('wallet', 'wallets', 'wallet_id', '{amount}');

		$dataStructure->addManyToOne('group', 'groups', 'group_id', '{name} - {type}');

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
			'{name}'
		);

		return $source;
	}

}
