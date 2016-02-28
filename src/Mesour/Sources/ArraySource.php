<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Mesour;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class ArraySource implements ISource
{

    const DATE = 'date';

    const _DATE_MARK = '__date_';

    private $primaryKey = 'id';

    private $relations = [];

    private $related = [];

    /** @var Mesour\ArrayManage\Searcher\Select */
    protected $select;

    protected $dataArr = [];

    /** @var null|array */
    protected $lastFetchAllResult = null;

    protected $structure = [];

    /**
     * @param array $data
     * @param array $relations
     * @throws MissingRequiredException
     */
    public function __construct(array $data, array $relations = [])
    {
        if (!class_exists(Mesour\ArrayManage\Searcher\Select::class)) {
            throw new MissingRequiredException('Array data source required composer package "mesour/array-manager".');
        }
        $this->dataArr = $data;
        $this->relations = $relations;
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
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSelect()->getTotalCount();
    }

    public function where($column, $value = null, $condition = null, $operator = 'and')
    {
        if (isset($this->structure[$column]) && $this->structure[$column] === self::DATE) {
            $value = $this->fixDate($value);
            $column = self::_DATE_MARK . $column;
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
        $this->lastFetchAllResult = $out;

        return $out;
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
            return false;
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

    public function setStructure(array $structure)
    {
        $this->structure = $structure;

        return $this;
    }

    public function join($table, $key, $column, $columnAlias, $primaryKey = 'id', $left = false)
    {
        $this->addReference($table, $columnAlias, $primaryKey);

        $related = $this->getReferencedSource($table);
        foreach ($this->dataArr as $_key => $item) {
            $current = clone $related;
            $item_name = is_string($columnAlias) ? $columnAlias : $column;
            if (isset($item[$key])) {
                $_item = $current->where($related->getPrimaryKey(), $item[$key], Mesour\ArrayManage\Searcher\Condition::EQUAL)->fetch();
                if (isset($_item[$column])) {
                    $this->dataArr[$_key][$item_name] = $_item[$column];
                } else {
                    $this->dataArr[$_key][$item_name] = null;
                }
                $this->select = null;
            } elseif ($left) {
                $this->dataArr[$_key][$item_name] = null;
            } else {
                throw new Exception('Column ' . $key . ' does not exist in data array.');
            }
            unset($current);
        }

        return $this;
    }

    public function addReference($table, $column, $primaryKey = 'id')
    {
        if (!isset($this->related[$table])) {
            $this->related[$table]['primary_key'] = $primaryKey;
        }
        $this->related[$table]['columns'][] = $column;
        $this->related[$table]['columns'] = array_unique($this->related[$table]['columns']);

        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws Exception
     */
    public function getReferencedSource($table)
    {
        if (!$this->hasReference($table)) {
            throw new Exception('Relation ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table]) || !$this->relations[$table] instanceof ISource) {
            if (!is_array($this->relations[$table])) {
                throw new Exception('Relation ' . $table . ' does not exists.');
            }
            $this->relations[$table] = $source = new static($this->relations[$table]);
            $source->setPrimaryKey($this->related[$table]['primary_key']);
        }

        return $this->relations[$table];
    }

    /**
     * @param $table
     * @return bool
     */
    public function hasReference($table)
    {
        return isset($this->related[$table]);
    }

    /**
     * @return array
     */
    public function getReferenceSettings()
    {
        return $this->related;
    }

    /**
     * @return Mesour\ArrayManage\Searcher\Select
     * @throws Exception
     */
    protected function getSelect()
    {
        if (!$this->select) {
            if (count($this->structure)) {
                foreach ($this->structure as $name => $value) {
                    if ($value === self::DATE) {
                        foreach ($this->dataArr as $key => $item) {
                            if (!array_key_exists($name, $item)) {
                                throw new Exception('Column ' . $name . ' does not exists in source array.');
                            }
                            $this->dataArr[$key][self::_DATE_MARK . $name] = $this->fixDate($item[$name]);
                        }
                    }
                }
            }
            $this->select = new Mesour\ArrayManage\Searcher\Select($this->dataArr);
        }

        return $this->select;
    }

    protected function removeStructureDate(&$out)
    {
        foreach ($this->structure as $name => $type) {
            if ($type === self::DATE) {
                unset($out[self::_DATE_MARK . $name]);
            }
        }
    }

    protected function makeArrayHash(array $val)
    {
        return ArrayHash::from($val);
    }

}