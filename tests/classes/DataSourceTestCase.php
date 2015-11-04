<?php
namespace Test;

require_once __DIR__ . '/../bootstrap.php';

use Mesour\Sources\ISource;
use Mesour\Sources\NetteDbSource;
use Tester\Assert;

abstract class DataSourceTestCase extends \Tester\TestCase
{
    CONST DEFAULT_PRIMARY_KEY = 'id',
        OWN_PRIMARY_KEY = 'user_id',
        FULL_USER_COUNT = 20,
        COLUMN_COUNT = 11,
        ACTIVE_COUNT = 10,
        INACTIVE_STATUS = 0,
        GROUPS_COUNT = 3,
        ACTIVE_STATUS = 1,
        DATE_BIGGER = '2014-09-01 06:27:32',
        DATE_BIGGER_COUNT = 12,
        CHECKERS_COUNT = 8,
        CUSTOM_COUNT = 7,
        CUSTOM_OR_COUNT = 3,
        LIMIT = 5,
        OFFSET = 2;

    protected $credentials = array(
        'dsn' => "mysql:host=127.0.0.1;dbname=sources_test",
        'user' => 'root',
        'password' => 'root',
        'database' => '',
    );

    private $pairs = array(
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
    );

    protected function matchPrimaryKey(ISource $source)
    {
        Assert::same(self::DEFAULT_PRIMARY_KEY, $source->getPrimaryKey());
        $source->setPrimaryKey(self::OWN_PRIMARY_KEY);
        Assert::same(self::OWN_PRIMARY_KEY, $source->getPrimaryKey());
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
        Assert::same($this->pairs, $source->fetchPairs('user_id', 'name'));
    }

    protected function matchOffset(ISource $source)
    {
        $source->applyLimit(self::LIMIT, self::OFFSET);
        $all_data = $this->assertLimit($source);
        $first_user = reset($all_data);
        Assert::equal((string)(self::OFFSET + 1), (string)$first_user['user_id']);
    }

    protected function matchWhere(ISource $source)
    {
        $this->assertCounts($source, self::ACTIVE_COUNT);
    }

    protected function matchWhereDate(ISource $source)
    {
        $this->assertCounts($source, self::DATE_BIGGER_COUNT);
    }

    protected function matchEmpty(ISource $source)
    {
        $this->assertCounts($source, 0, 0, 0);
    }

    private function assertCounts(ISource $source, $active_count, $full = self::FULL_USER_COUNT, $columns = self::COLUMN_COUNT)
    {
        $itemData = $source->fetch();
        if($itemData && $source instanceof NetteDbSource) {
            $itemData = $itemData->toArray();
        }
        Assert::count($columns, $itemData);
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