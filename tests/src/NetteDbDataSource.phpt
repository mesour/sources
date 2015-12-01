<?php

use Mesour\Sources\NetteDbSource;
use Tester\Assert;

require_once __DIR__ . '/../classes/DataSourceTestCase.php';

class NetteDbSourceTest extends \Test\DataSourceTestCase
{

    /**
     * @var \Nette\Database\Connection
     */
    private $connection;

    /**
     * @var \Nette\Database\Context
     */
    private $context;

    /**
     * @var \Nette\Database\Table\Selection
     */
    private $user;

    /**
     * @var \Nette\Database\Table\Selection
     */
    private $empty;

    public function __construct()
    {
        $this->connection = new Nette\Database\Connection($this->credentials['dsn'], $this->credentials['user'], $this->credentials['password']);

        $cacheMemoryStorage = new Nette\Caching\Storages\MemoryStorage;

        $structure = new Nette\Database\Structure($this->connection, $cacheMemoryStorage);
        $conventions = new Nette\Database\Conventions\DiscoveredConventions($structure);
        $this->context = new Nette\Database\Context($this->connection, $structure, $conventions, $cacheMemoryStorage);

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

        Assert::same(FALSE, $source->isRelated('groups'));

        $source->setRelated('groups', 'group_id', 'name', 'group_name');

        Assert::same(TRUE, $source->isRelated('groups'));

        $related = $source->related('groups');

        Assert::type('Mesour\Sources\NetteDbSource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
    }

}

$test = new NetteDbSourceTest();
$test->run();