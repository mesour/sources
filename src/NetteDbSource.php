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
use Nette\Database\Table\Selection;



/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class NetteDbSource implements ISource
{

    private $primary_key = 'id';

    private $related = array();

    private $relations = array();

    /**
     * @var Selection
     */
    private $nette_table;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var array
     */
    private $where_arr = array();

    /**
     * @var integer
     */
    private $limit;

    /**
     * @var integer
     */
    private $offset = 0;

    private $total_count = 0;

    /**
     * @param Selection $selection
     * @param Context $context
     */
    public function __construct(Selection $selection, Context $context = NULL)
    {
        $this->context = $context;
        $this->nette_table = $selection;
        $this->total_count = $selection->count('*');
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
        return $this->total_count;
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
        $selection = clone $this->nette_table;
        if ($where) {
            foreach ($this->where_arr as $conditions) {
                call_user_func_array(array($selection, 'where'), $conditions);
            }
        }
        if ($limit) {
            $selection->limit($this->limit, $this->offset);
        }
        return $selection;
    }

    /**
     * Get searched values with applied limit, offset and where
     * @return array
     */
    public function fetchAll()
    {
        $output = array();
        $selection = $this->getSelection();
        foreach ($selection as $data) {
            $output[] = $data->toArray();
        }
        return $output;
    }

    public function orderBy($row, $sorting = 'ASC')
    {
        return $this->nette_table->order($row . ' ' . $sorting);
    }

    /**
     * Return first element from data
     * @return array
     */
    public function fetch()
    {
        if ($this->total_count > 0) {
            return $this->getSelection(FALSE, FALSE)->limit(1, 0)->fetch()->toArray();
        } else {
            return array();
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
        return $this->primary_key;
    }

    public function setPrimaryKey($primary_key)
    {
        $this->primary_key = $primary_key;
        return $this;
    }

    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id')
    {
        if (is_null($this->context)) {
            throw new Exception('Related require set Nette database context in constructor.');
        }
        if (count($this->related) === 0) {
            $this->nette_table->select('*');
        }
        $this->related[$table] = array($table, $key, $column, $as, $primary);

        $this->nette_table->select($table . '.' . $column . (!is_null($as) ? (' AS ' . $as) : ''));

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

}
