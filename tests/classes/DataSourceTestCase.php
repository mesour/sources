<?php
namespace Mesour\Sources\Tests;

use Mesour\Sources\DoctrineSource;
use Mesour\Sources\ISource;
use Tester\Assert;
use \Tester\TestCase;

abstract class DataSourceTestCase extends TestCase
{
    CONST DEFAULT_PRIMARY_KEY = 'id',
        OWN_PRIMARY_KEY = 'user_id',
        OWN_PRIMARY_KEY_DOCTRINE = 'userId',
        FULL_USER_COUNT = 20,
        COLUMN_COUNT = 11,
        FIRST_GROUP_NAME = 'Group 1',
        ACTIVE_COUNT = 10,
        INACTIVE_STATUS = 0,
        GROUPS_COUNT = 3,
        USERS_WITH_FIRST_GROUP = 7,
        ACTIVE_STATUS = 1,
        DATE_BIGGER = '2014-09-01 06:27:32',
        DATE_BIGGER_COUNT = 12,
        CHECKERS_COUNT = 8,
        CUSTOM_COUNT = 7,
        CUSTOM_OR_COUNT = 3,
        LIMIT = 5,
        OFFSET = 2;

    protected $credentials = [
        'user' => 'root',
        'password' => '',
    ];

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
        $this->databaseFactory = new DatabaseFactory(
            '127.0.0.1', $this->credentials['user'],
            $this->credentials['password'], 'mesour_sources_'
        );
        $this->baseConnection = $this->databaseFactory->create();
    }

    public function __destruct()
    {
        $this->databaseFactory->destroy($this->baseConnection);
    }

    private function getOwnPrimaryKey(ISource $source)
    {
        return $source instanceof DoctrineSource ? self::OWN_PRIMARY_KEY_DOCTRINE : self::OWN_PRIMARY_KEY;
    }

    protected function matchPrimaryKey(ISource $source)
    {
        Assert::same(self::DEFAULT_PRIMARY_KEY, $source->getPrimaryKey());
        $source->setPrimaryKey($this->getOwnPrimaryKey($source));
        Assert::same($this->getOwnPrimaryKey($source), $source->getPrimaryKey());
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
        Assert::same($this->pairs, $source->fetchPairs($this->getOwnPrimaryKey($source), 'name'));
    }

    protected function matchOffset(ISource $source)
    {
        $source->applyLimit(self::LIMIT, self::OFFSET);
        $all_data = $this->assertLimit($source);
        $first_user = reset($all_data);
        Assert::equal((string)(self::OFFSET + 1), (string)$first_user[$this->getOwnPrimaryKey($source)]);
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
        $this->assertCounts($source, 0, 0, 0, TRUE);
    }

    private function assertCounts(ISource $source, $active_count, $full = self::FULL_USER_COUNT, $columns = self::COLUMN_COUNT, $fetch = FALSE)
    {
        $itemData = $source->fetch();
        if (!$fetch) {
            Assert::count($columns, $itemData);
        } else {
            Assert::same(FALSE, $itemData);
        }
        Assert::count($active_count, $source->fetchAll());
        Assert::same($full, $source->getTotalCount());
        Assert::same($active_count, $source->count());
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