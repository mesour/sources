<?php

use Mesour\Sources\DoctrineSource;
use Tester\Assert;

require_once __DIR__ . '/../classes/DataSourceTestCase.php';

class DoctrineSourceTest extends \Test\DataSourceTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    private $user;

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    private $empty;

    private $primaryKey = 'user_id';

    private $columnMapping = [
        'user_id' => 'u.userId',
        'group_id' => 'u.groups',
        'last_login' => 'u.lastLogin',
        'group_name' => 'gr.name',
    ];

    public function __construct()
    {
        $this->entityManager = require_once __DIR__ . '/../../demo/bootstrap.php';
        $this->user = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from('user', 'u');
        $this->empty = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from('emptyTable', 'e');
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
        $source = new DoctrineSource($this->empty, $this->columnMapping);
        $source->setPrimaryKey($this->primaryKey);
        $this->matchEmpty($source);
    }

    public function testRelated()
    {
        $source = new DoctrineSource($this->user, $this->columnMapping);
        $source->setPrimaryKey($this->primaryKey);

        Assert::same(FALSE, $source->isRelated('group'));

        $source->setRelated('groups', 'group_id', 'name', 'group_name');

        Assert::same(TRUE, $source->isRelated('groups'));

        $related = $source->related('groups');

        Assert::type('Mesour\Sources\DoctrineSource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
    }

}

$test = new DoctrineSourceTest();
$test->run();