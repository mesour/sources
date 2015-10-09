<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources\MesourDb;

use Mesour\Components\BadStateException;
use Mesour\Database\Connection;
use Mesour\Database\QueryBuilder\SelectQueryBuilder;
use Mesour\Sources\ISource;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class MesourDbSource implements ISource
{

    private $primaryKey = 'id';

    private $related = [];

    private $relations = [];

    /** @var Connection */
    private $connection = NULL;

    /** @var SelectQueryBuilder */
    protected $queryBuilder;

    private $whereArr = [];

    /** @var int|null */
    private $limit = NULL;

    /** @var int */
    private $offset = 0;

    private $totalCount = NULL;

    /**
     * @param SelectQueryBuilder $queryBuilder
     * @param Connection|NULL $connection
     */
    public function __construct(SelectQueryBuilder $queryBuilder, Connection $connection = NULL)
    {
        $this->queryBuilder = $queryBuilder;
        $this->connection = $connection;

        $primaryKey = $this->queryBuilder->getTable()->getPrimaryName();
        if (!is_null($primaryKey)) {
            $this->primaryKey = $primaryKey;
        }
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get array data count
     *
     * @return Integer
     */
    public function getTotalCount()
    {
        if(is_null($this->totalCount)) {
            $this->totalCount = $this->getQueryBuilder(FALSE, FALSE)->count();
        }
        return $this->totalCount;
    }

    /**
     * @param mixed $args Mesour\Database args
     * @return $this
     */
    public function where($args)
    {
        $this->whereArr[] = func_get_args();
        return $this;
    }

    /**
     * Apply limit and offset
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function applyLimit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get count after applied where
     *
     * @return int
     */
    public function count()
    {
        return $this->getQueryBuilder(TRUE, FALSE)->count();
    }

    /**
     * Get searched values with applied limit, offset and where
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->getQueryBuilder()->fetchAll();
    }

    public function orderBy($row, $sorting = 'ASC')
    {
        $this->queryBuilder->orderBy($row . ' ' . $sorting);
    }

    /**
     * Return first element from data
     *
     * @return array
     */
    public function fetch()
    {
        $data = $this->getQueryBuilder()->fetch();
        if (!$data) {
            return [];
        }
        return $data;
    }

    /**
     * @param string $key
     * @param string $value
     * @return array
     */
    public function fetchPairs($key, $value)
    {
        return $this->getQueryBuilder()->fetchPairs($key, $value);
    }

    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id')
    {
        if (is_null($this->connection)) {
            throw new BadStateException('Related require set Mesour connection in constructor.');
        }

        $this->related[$table] = array($table, $key, $column, $as, $primary);

        $this->queryBuilder->column($table . '.' . $column . ($as ? (' ' . $as) : ''));

        $this->queryBuilder->join($table, $table . '.' . $key . ' = ' . $primary);

        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws BadStateException
     */
    public function related($table)
    {
        if (!$this->isRelated($table)) {
            throw new BadStateException('Relation ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = new static($this->connection->table($table)->select(), $this->connection);
        }
        return $this->relations[$table];
    }

    /**
     * @param $table
     * @return bool
     */
    public function isRelated($table)
    {
        return isset($this->related[$table]);
    }

    /**
     * @return array
     */
    public function getAllRelated()
    {
        return $this->related;
    }

    /**
     * @param bool|TRUE $limit
     * @param bool|TRUE $where
     * @return SelectQueryBuilder
     */
    protected function getQueryBuilder($limit = TRUE, $where = TRUE)
    {
        $builder = clone $this->queryBuilder;
        if ($where) {
            foreach ($this->whereArr as $conditions) {
                call_user_func_array(array($builder, 'where'), $conditions);
            }
        }
        if ($limit) {
            $builder->limit($this->limit);
            if ($this->offset > 0) {
                $builder->offset($this->offset);
            }
        }
        return $builder;
    }

}