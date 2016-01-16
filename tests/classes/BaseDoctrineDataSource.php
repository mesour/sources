<?php

namespace Mesour\Sources\Tests;


use Mesour\Sources\DoctrineSource;
use Mesour\Sources\Exception;
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
        'group_name' => 'gr.name',
    ];

    public function __construct()
    {
        parent::__construct();

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

    public function testRelated()
    {
        $source = new DoctrineSource($this->user, $this->columnMapping);
        $source->setPrimaryKey($this->primaryKey);

        Assert::same(FALSE, $source->isRelated(Groups::class));

        $source->setRelated(Groups::class, 'group_id', 'name', 'group_name');

        Assert::same(TRUE, $source->isRelated(Groups::class));

        $related = $source->related(Groups::class);

        Assert::type('Mesour\Sources\DoctrineSource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
    }

    public function testFetchLastRawRows()
    {
        $source = new DoctrineSource($this->user, $this->columnMapping);
        $source->setPrimaryKey($this->primaryKey);

        Assert::exception(function () use ($source) {
            $source->fetchLastRawRows();
        }, Exception::class);

        $source->fetchAll();

        $rawData = $source->fetchLastRawRows();

        Assert::count(self::FULL_USER_COUNT, $rawData);
        foreach ($rawData as $item) {
            Assert::type(User::class, $item);
        }
    }

}