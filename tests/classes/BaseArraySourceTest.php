<?php

namespace Mesour\Sources\Tests;

use Mesour\ArrayManage\Searcher\Condition;
use Mesour\Sources\ArraySource;
use Mesour\Sources\Exception;
use Tester\Assert;
use Mesour\Sources\ArrayHash;

abstract class BaseArraySourceTest extends DataSourceTestCase
{

    static public $user = [
        ['user_id' => '1', 'action' => '0', 'group_id' => '1', 'name' => 'John', 'surname' => 'Doe', 'email' => 'john.doe@test.xx', 'last_login' => null, 'amount' => '1561.456542', 'avatar' => '/avatar/01.png', 'order' => '100', 'timestamp' => '1418255325'],
        ['user_id' => '2', 'action' => '1', 'group_id' => '2', 'name' => 'Peter', 'surname' => 'Larson', 'email' => 'peter.larson@test.xx', 'last_login' => '2014-09-09 13:37:32', 'amount' => '15220.654', 'avatar' => '/avatar/02.png', 'order' => '160', 'timestamp' => '1418255330'],
        ['user_id' => '3', 'action' => '1', 'group_id' => '2', 'name' => 'Claude', 'surname' => 'Graves', 'email' => 'claude.graves@test.xx', 'last_login' => '2014-09-02 14:17:32', 'amount' => '9876.465498', 'avatar' => '/avatar/03.png', 'order' => '180', 'timestamp' => '1418255311'],
        ['user_id' => '4', 'action' => '0', 'group_id' => '3', 'name' => 'Stuart', 'surname' => 'Norman', 'email' => 'stuart.norman@test.xx', 'last_login' => '2014-09-09 18:39:18', 'amount' => '98766.2131', 'avatar' => '/avatar/04.png', 'order' => '120', 'timestamp' => '1418255328'],
        ['user_id' => '5', 'action' => '1', 'group_id' => '1', 'name' => 'Kathy', 'surname' => 'Arnold', 'email' => 'kathy.arnold@test.xx', 'last_login' => '2014-09-07 10:24:07', 'amount' => '456.987', 'avatar' => '/avatar/05.png', 'order' => '140', 'timestamp' => '1418155313'],
        ['user_id' => '6', 'action' => '0', 'group_id' => '3', 'name' => 'Jan', 'surname' => 'Wilson', 'email' => 'jan.wilson@test.xx', 'last_login' => '2014-09-03 13:15:22', 'amount' => '123', 'avatar' => '/avatar/06.png', 'order' => '150', 'timestamp' => '1418255318'],
        ['user_id' => '7', 'action' => '0', 'group_id' => '1', 'name' => 'Alberta', 'surname' => 'Erickson', 'email' => 'alberta.erickson@test.xx', 'last_login' => '2014-08-06 13:37:17', 'amount' => '98753.654', 'avatar' => '/avatar/07.png', 'order' => '110', 'timestamp' => '1418255327'],
        ['user_id' => '8', 'action' => '1', 'group_id' => '3', 'name' => 'Ada', 'surname' => 'Wells', 'email' => 'ada.wells@test.xx', 'last_login' => '2014-08-12 11:25:16', 'amount' => '852.3654', 'avatar' => '/avatar/08.png', 'order' => '70', 'timestamp' => '1418255332'],
        ['user_id' => '9', 'action' => '0', 'group_id' => '2', 'name' => 'Ethel', 'surname' => 'Figueroa', 'email' => 'ethel.figueroa@test.xx', 'last_login' => '2014-09-05 10:23:26', 'amount' => '45695.986', 'avatar' => '/avatar/09.png', 'order' => '20', 'timestamp' => '1417255305'],
        ['user_id' => '10', 'action' => '1', 'group_id' => '3', 'name' => 'Ian', 'surname' => 'Goodwin', 'email' => 'ian.goodwin@test.xx', 'last_login' => '2014-09-04 12:26:19', 'amount' => '1236.9852', 'avatar' => '/avatar/10.png', 'order' => '130', 'timestamp' => '1418255331'],
        ['user_id' => '11', 'action' => '1', 'group_id' => '2', 'name' => 'Francis', 'surname' => 'Hayes', 'email' => 'francis.hayes@test.xx', 'last_login' => '2014-09-03 10:16:17', 'amount' => '5498.345', 'avatar' => '/avatar/11.png', 'order' => '0', 'timestamp' => '1417255293'],
        ['user_id' => '12', 'action' => '0', 'group_id' => '1', 'name' => 'Erma', 'surname' => 'Burns', 'email' => 'erma.burns@test.xx', 'last_login' => '2014-07-02 15:42:15', 'amount' => '63287.9852', 'avatar' => '/avatar/12.png', 'order' => '60', 'timestamp' => '1418255316'],
        ['user_id' => '13', 'action' => '1', 'group_id' => '3', 'name' => 'Kristina', 'surname' => 'Jenkins', 'email' => 'kristina.jenkins@test.xx', 'last_login' => '2014-08-20 14:39:43', 'amount' => '74523.96549', 'avatar' => '/avatar/13.png', 'order' => '40', 'timestamp' => '1418255334'],
        ['user_id' => '14', 'action' => '0', 'group_id' => '3', 'name' => 'Virgil', 'surname' => 'Hunt', 'email' => 'virgil.hunt@test.xx', 'last_login' => '2014-08-12 16:09:38', 'amount' => '65654.6549', 'avatar' => '/avatar/14.png', 'order' => '30', 'timestamp' => '1418255276'],
        ['user_id' => '15', 'action' => '1', 'group_id' => '1', 'name' => 'Max', 'surname' => 'Martin', 'email' => 'max.martin@test.xx', 'last_login' => '2014-09-01 12:14:20', 'amount' => '541236.5495', 'avatar' => '/avatar/15.png', 'order' => '170', 'timestamp' => '1418255317'],
        ['user_id' => '16', 'action' => '1', 'group_id' => '2', 'name' => 'Melody', 'surname' => 'Manning', 'email' => 'melody.manning@test.xx', 'last_login' => '2014-09-02 12:26:20', 'amount' => '9871.216', 'avatar' => '/avatar/16.png', 'order' => '50', 'timestamp' => '1418255281'],
        ['user_id' => '17', 'action' => '1', 'group_id' => '3', 'name' => 'Catherine', 'surname' => 'Todd', 'email' => 'catherine.todd@test.xx', 'last_login' => '2014-06-11 15:14:39', 'amount' => '100.2', 'avatar' => '/avatar/17.png', 'order' => '10', 'timestamp' => '1416255313'],
        ['user_id' => '18', 'action' => '0', 'group_id' => '1', 'name' => 'Douglas', 'surname' => 'Stanley', 'email' => 'douglas.stanley@test.xx', 'last_login' => '2014-04-16 15:22:18', 'amount' => '900', 'avatar' => '/avatar/18.png', 'order' => '90', 'timestamp' => '1416255332'],
        ['user_id' => '19', 'action' => '0', 'group_id' => '2', 'name' => 'Patti', 'surname' => 'Diaz', 'email' => 'patti.diaz@test.xx', 'last_login' => '2014-09-11 12:17:16', 'amount' => '1500', 'avatar' => '/avatar/19.png', 'order' => '80', 'timestamp' => '1418255275'],
        ['user_id' => '20', 'action' => '0', 'group_id' => '1', 'name' => 'John', 'surname' => 'Petterson', 'email' => 'john.petterson@test.xx', 'last_login' => '2014-10-10 10:10:10', 'amount' => '2500', 'avatar' => '/avatar/20.png', 'order' => '190', 'timestamp' => '1418255275'],
    ];

    protected $relations = [
        'group' => [
            ['id' => '2', 'name' => 'Group 2', 'type' => 'admin'],
            ['id' => '1', 'name' => 'Group 1', 'type' => 'moderator'],
            ['id' => '3', 'name' => 'Group 3', 'type' => 'moderator'],
        ],
    ];

    public function testPrimaryKey()
    {
        $source = new ArraySource(self::$user);
        $this->matchPrimaryKey($source);
    }

    public function testTotalCount()
    {
        $source = new ArraySource(self::$user);
        $this->matchTotalCount($source);
    }

    public function testFetch()
    {
        $source = new ArraySource(self::$user);
        Assert::equal($source->fetch(), ArrayHash::from(reset(self::$user)));
    }

    public function testFetchPairs()
    {
        $source = new ArraySource(self::$user);

        $this->matchPairs($source);
    }

    public function testLimit()
    {
        $source = new ArraySource(self::$user);
        $this->matchLimit($source);
    }

    public function testOffset()
    {
        $source = new ArraySource(self::$user);
        $this->matchOffset($source);
    }

    public function testWhere()
    {
        $source = new ArraySource(self::$user);
        $source->where('action', self::ACTIVE_STATUS, Condition::EQUAL);
        $this->matchWhere($source);
    }

    public function testWhereDate()
    {
        $source = new ArraySource(self::$user);
        $source->setStructure([
            'last_login' => 'date',
        ]);
        $source->where('last_login', self::DATE_BIGGER, Condition::BIGGER);
        $this->matchWhereDate($source);
    }

    public function testEmpty()
    {
        $source = new ArraySource([]);
        $this->matchEmpty($source);
    }

    public function testRelated()
    {
        $source = new ArraySource(self::$user, $this->relations);

        Assert::same(false, $source->isRelated('group'));

        $source->join('group', 'group_id', 'name', 'group_name');
        $source->join('group', 'group_id', 'type', 'group_type');

        Assert::same(true, $source->isRelated('group'));

        $firstRow = $source->fetch();
        Assert::count(self::COLUMN_COUNT + 2, $firstRow);
        Assert::same(self::FIRST_GROUP_NAME, $firstRow['group_name']);

        $related = $source->related('group');

        Assert::type('Mesour\Sources\ArraySource', $related);
        Assert::same(self::GROUPS_COUNT, $related->getTotalCount());
        Assert::same(count($source->fetch()), self::COLUMN_COUNT + 2);

        Assert::same([
            'group' => [
                'primary_key' => 'id',
                'columns' => ['group_name', 'group_type'],
            ],
        ], $source->getAllRelated());

        $source->where('group_name', 'Group 1', Condition::EQUAL);

        Assert::count(self::USERS_WITH_FIRST_GROUP, $source->fetchAll());
    }

    public function testFetchLastRawRows()
    {
        $source = new ArraySource(self::$user, $this->relations);
        $source->setPrimaryKey('user_id');

        Assert::exception(function () use ($source) {
            $source->fetchLastRawRows();
        }, Exception::class);

        $source->fetchAll();

        $rawData = $source->fetchLastRawRows();

        Assert::count(self::FULL_USER_COUNT, $rawData);
        foreach ($rawData as $item) {
            Assert::type(ArrayHash::class, $item);
        }
    }

}