<?php

namespace Mesour\Sources\Tests;

use Mesour\Sources\Exception;
use Mesour\Sources\NetteDbSource;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;
use Nette\Database;

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

    protected $tableName = 'user';

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
        $source = new NetteDbSource($this->user, $this->tableName);
        $this->matchPrimaryKey($source);
    }

    public function testTotalCount()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $this->matchTotalCount($source);
    }

    public function testFetchPairs()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $this->matchPairs($source);
    }

    public function testLimit()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $this->matchLimit($source);
    }

    public function testOffset()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $this->matchOffset($source);
    }

    public function testWhere()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $source->where('action = ?', self::ACTIVE_STATUS);
        $this->matchWhere($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
    }

    public function testWhereDate()
    {
        $source = new NetteDbSource($this->user, $this->tableName);
        $source->where('last_login > ?', self::DATE_BIGGER);
        $this->matchWhereDate($source, self::FULL_USER_COUNT, self::COLUMN_COUNT);
    }

    public function testEmpty()
    {
        $source = new NetteDbSource($this->empty, 'empty');
        $this->matchEmpty($source);
    }

    public function testRelated()
    {
        $selection = clone $this->user;
        $selection->select('user.*')
            ->select('group.name group_name')
            ->select('group.type group_type');

        $source = new NetteDbSource($selection, $this->columnMapping, $this->context);

        Assert::same(false, $source->hasReference('group'));

        $source->addReference('group', 'group_name');
        $source->addReference('group', 'group_type');

        $firstRow = $source->fetch();
        Assert::count(self::COLUMN_RELATION_COUNT, $firstRow);
        Assert::same(self::FIRST_GROUP_NAME, $firstRow['group_name']);

        Assert::same(true, $source->hasReference('group'));

        $related = $source->getReferencedSource('group');

        Assert::type('Mesour\Sources\NetteDbSource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
        Assert::same(count($source->fetch()), self::COLUMN_RELATION_COUNT);

        Assert::same([
            'group' => [
                'primary_key' => 'id',
                'columns' => ['group_name', 'group_type'],
            ],
        ], $source->getReferenceSettings());

        $source->where('group.name = ?', 'Group 1');

        Assert::count(self::USERS_WITH_FIRST_GROUP, $source->fetchAll());
    }

    public function testFetchLastRawRows()
    {
        $source = new NetteDbSource($this->user, $this->tableName, $this->context);
        $source->setPrimaryKey('user_id');

        Assert::exception(function () use ($source) {
            $source->fetchLastRawRows();
        }, Exception::class);

        $source->fetchAll();

        $rawData = $source->fetchLastRawRows();

        Assert::count(self::FULL_USER_COUNT, $rawData);
        foreach ($rawData as $item) {
            Assert::type(Database\Table\ActiveRow::class, $item);
        }
    }

}