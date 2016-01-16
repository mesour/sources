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
    private $connection;

    /** @var \Nette\Database\Context */
    private $context;

    /** @var \Nette\Database\Table\Selection */
    private $user;

    /** @var \Nette\Database\Table\Selection */
    private $empty;

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

        $this->user = $this->context->table('user');
        $this->empty = $this->context->table('empty');
    }

    public function testPrimaryKey()
    {
        $source = new NetteDbSource($this->user);
        $this->matchPrimaryKey($source);
    }

    public function testTotalCount()
    {
        $source = new NetteDbSource($this->user);
        $this->matchTotalCount($source);
    }

    public function testFetchPairs()
    {
        $source = new NetteDbSource($this->user);
        $this->matchPairs($source);
    }

    public function testLimit()
    {
        $source = new NetteDbSource($this->user);
        $this->matchLimit($source);
    }

    public function testOffset()
    {
        $source = new NetteDbSource($this->user);
        $this->matchOffset($source);
    }

    public function testWhere()
    {
        $source = new NetteDbSource($this->user);
        $source->where('action = ?', self::ACTIVE_STATUS);
        $this->matchWhere($source);
    }

    public function testWhereDate()
    {
        $source = new NetteDbSource($this->user);
        $source->where('last_login > ?', self::DATE_BIGGER);
        $this->matchWhereDate($source);
    }

    public function testEmpty()
    {
        $source = new NetteDbSource($this->empty);
        $this->matchEmpty($source);
    }

    public function testRelated()
    {
        $source = new NetteDbSource($this->user, $this->context);

        Assert::same(FALSE, $source->isRelated('group'));

        $source->setRelated('group', 'group_id', 'name', 'group_name');

        Assert::same(TRUE, $source->isRelated('group'));

        $related = $source->related('group');

        Assert::type('Mesour\Sources\NetteDbSource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
    }

    public function testFetchLastRawRows()
    {
        $source = new NetteDbSource($this->user, $this->context);
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