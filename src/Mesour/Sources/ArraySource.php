<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Mesour\ArrayManage\Searcher\Condition;
use Mesour\ArrayManage\Searcher\Select;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class ArraySource implements ISource
{

    private $primary_key = 'id';

    private $relations = [];

    private $related = [];

    /**
     * @var Select
     */
    protected $select;

    protected $data_arr = [];

    protected $structure = [];

    /**
     * @param array $data
     * @param array $relations
     * @throws MissingRequiredException
     */
    public function __construct(array $data, array $relations = [])
    {
        if (!class_exists('\Mesour\ArrayManage\Searcher\Select')) {
            throw new MissingRequiredException('Array data source required composer package "mesour/array-manager".');
        }
        $this->data_arr = $data;
        $this->relations = $relations;
    }

    public function setPrimaryKey($primary_key)
    {
        $this->primary_key = $primary_key;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    /**
     * Get array data count
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSelect()->getTotalCount();
    }

    public function where($column, $value = NULL, $condition = NULL, $operator = 'and')
    {
        if (isset($this->structure[$column]) && $this->structure[$column] === 'date') {
            $value = $this->fixDate($value);
            $column = '__date_' . $column;
        }

        $this->getSelect()->where($column, $value, $condition, $operator);
        return $this;
    }

    static public function fixDate($date)
    {
        return is_numeric($date) ? $date : strtotime($date);
    }

    /**
     * Apply limit and offset
     *
     * @param int $limit
     * @param int $offset
     */
    public function applyLimit($limit, $offset = 0)
    {
        $this->getSelect()->limit($limit);
        $this->getSelect()->offset($offset);
    }

    /**
     * Get count after applied where
     *
     * @return int
     */
    public function count()
    {
        return $this->getSelect()->count();
    }

    /**
     * Get searched values with applied limit, offset and where
     *
     * @return ArrayHash[]
     */
    public function fetchAll()
    {
        $out = $this->getSelect()->fetchAll();
        if (count($this->structure) > 0) {
            foreach ($out as $key => $val) {
                $this->removeStructureDate($out[$key]);
            }
        }
        foreach ($out as $key => $val) {
            $out[$key] = $this->makeArrayHash($val);
        }
        return $out;
    }

    protected function makeArrayHash(array $val)
    {
        return ArrayHash::from($val);
    }

    public function orderBy($row, $sorting = 'ASC')
    {
        $this->getSelect()->orderBy($row, $sorting);
    }

    /**
     * Return first element from data
     *
     * @return ArrayHash|FALSE
     */
    public function fetch()
    {
        $data = $this->getSelect()->fetch();
        if (!$data) {
            return FALSE;
        }
        if (count($this->structure) > 0) {
            $this->removeStructureDate($data);
        }
        return $this->makeArrayHash($data);
    }

    /**
     * @param string $key
     * @param string $value
     * @return array
     */
    public function fetchPairs($key, $value)
    {
        $data = $this->getSelect()->column($key)->column($value)
            ->fetchAll();

        $output = [];
        foreach ($data as $item) {
            $output[$item[$key]] = $item[$value];
        }
        return $output;
    }

    protected function removeStructureDate(&$out)
    {
        foreach ($this->structure as $name => $type) {
            switch ($type) {
                case 'date':
                    unset($out['__date_' . $name]);
                    break;
            }
        }
    }

    public function setStructure(array $structure)
    {
        $this->structure = $structure;
        return $this;
    }

    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id', $left = FALSE)
    {
        $this->related[$table] = [$table, $key, $column, $as, $primary];
        $related = $this->related($table, $key);

        foreach ($this->data_arr as $_key => $item) {
            $current = clone $related;
            if (isset($item[$key])) {
                $_item = $current->where($related->getPrimaryKey(), $item[$key], Condition::EQUAL)->fetch();
                $item_name = is_string($as) ? $as : $column;
                $this->data_arr[$_key][$item_name] = $_item[$column];
                $this->select = NULL;
            } else {
                throw new Exception('Column ' . $key . ' does not exist in data array.');
            }
            unset($current);
        }
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws Exception
     */
    public function related($table)
    {
        if (!$this->isRelated($table)) {
            throw new Exception('Relation ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table]) || !$this->relations[$table] instanceof ISource) {
            if(!is_array($this->relations[$table])) {
                throw new Exception('Relation ' . $table . ' does not exists.');
            }
            $this->relations[$table] = new static($this->relations[$table]);
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

    /**
     * @return Select
     * @throws Exception
     */
    protected function getSelect()
    {
        if (!$this->select) {
            if (count($this->structure)) {
                foreach ($this->structure as $name => $value) {
                    switch ($value) {
                        case 'date':
                            foreach ($this->data_arr as $key => $item) {
                                if (!array_key_exists($name, $item)) {
                                    throw new Exception('Column ' . $name . ' does not exists in source array.');
                                }
                                $this->data_arr[$key]['__date_' . $name] = $this->fixDate($item[$name]);
                            }
                            break;
                    }
                }
            }
            $this->select = new Select($this->data_arr);
        }
        return $this->select;
    }

}