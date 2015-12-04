<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class NetteDbSource implements ISource
{

    private $primaryKey = 'id';

    private $related = [];

    private $relations = [];

    /**
     * @var Selection
     */
    private $netteTable;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var array
     */
    private $where_arr = [];

    /**
     * @var integer
     */
    private $limit;

    /**
     * @var integer
     */
    private $offset = 0;

    private $totalCount = 0;

    /** @var null|array */
    protected $lastFetchAllResult = NULL;

    /**
     * @param Selection $selection
     * @param Context $context
     */
    public function __construct(Selection $selection, Context $context = NULL)
    {
        $this->context = $context;
        $this->netteTable = $selection;
        $this->totalCount = $selection->count('*');
    }

    /**
     * @return Selection
     */
    public function getTableSelection()
    {
        return $this->getSelection();
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @param mixed $args NetteDatabase args
     * @return $this
     */
    public function where($args)
    {
        $this->where_arr[] = func_get_args();
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
     * @return int
     */
    public function count()
    {
        $count = $this->getSelection()->count('*');
        $to_end = $count - ($this->offset + $this->limit);
        return !is_null($this->limit) && $this->limit < $count ? ($to_end < $this->limit ? $to_end : $this->limit) : $count;
    }

    protected function getSelection($limit = TRUE, $where = TRUE)
    {
        $selection = clone $this->netteTable;
        if ($where) {
            foreach ($this->where_arr as $conditions) {
                call_user_func_array([$selection, 'where'], $conditions);
            }
        }
        if ($limit) {
            $selection->limit($this->limit, $this->offset);
        }
        return $selection;
    }

    /**
     * Get searched values with applied limit, offset and where
     * @return ActiveRow[]
     */
    public function fetchAll()
    {
        $selection = $this->getSelection();
        $this->lastFetchAllResult = $selection->fetchAll();
        return $this->lastFetchAllResult;
    }

    /**
     * Get raw data from last fetchAll()
     *
     * IMPORTANT! fetchAll() must be called before call this method
     *
     * @return mixed
     * @throws Exception
     */
    public function fetchLastRawRows()
    {
        if (is_null($this->lastFetchAllResult)) {
            throw new Exception('Must call fetchAll() before call fetchLastRawRows() method.');
        }
        return $this->lastFetchAllResult;
    }

    public function orderBy($row, $sorting = 'ASC')
    {
        return $this->netteTable->order($row . ' ' . $sorting);
    }

    /**
     * Return first element from data
     * @return ActiveRow|FALSE
     */
    public function fetch()
    {
        if ($this->totalCount > 0) {
            return $this->getSelection(FALSE, FALSE)->fetch();
        } else {
            return FALSE;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return array
     */
    public function fetchPairs($key, $value)
    {
        return $this->getSelection()
            ->select($key)->select($value)
            ->fetchPairs($key, $value);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id', $left = FALSE)
    {
        if (is_null($this->context)) {
            throw new Exception('Related require set Nette database context in constructor.');
        }
        if (count($this->related) === 0) {
            if (method_exists($this->netteTable, 'getSqlBuilder')) {
                if (count($this->netteTable->getSqlBuilder()->getSelect()) === 0) {
                    $this->netteTable->select('*');
                }
            } else {
                $this->netteTable->select('*');
            }
        }
        $this->related[$table] = [$table, $key, $column, $as, $primary, $left];

        $this->netteTable->select($table . '.' . $column . (!is_null($as) ? (' AS ' . $as) : ''));

        return $this;
    }

    /**
     * @param $table
     * @return static
     * @throws Exception
     */
    public function related($table)
    {
        if (!$this->isRelated($table)) {
            throw new Exception('Relation ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = new static($this->context->table($table), $this->context);
            $this->relations[$table]->setPrimaryKey($this->related[$table][4]);
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

    protected function getRealColumnName($column_name)
    {
        foreach ($this->related as $name => $options) {
            if ($column_name === $options[3]) {
                return $options[0] . '.' . $options[2];
            }
        }
        return $column_name;
    }

}
