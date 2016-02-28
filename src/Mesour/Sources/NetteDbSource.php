<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Nette;


/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class NetteDbSource implements ISource
{

    private $primaryKey = 'id';

    private $related = [];

    private $relations = [];

    /** @var Nette\Database\Table\Selection */
    private $netteTable;

    /** @var Nette\Database\Context */
    private $context;

    /** @var array */
    private $whereArr = [];

    /** @var integer */
    private $limit;

    /** @var integer */
    private $offset = 0;

    private $totalCount = 0;

    /** @var null|array */
    protected $lastFetchAllResult = NULL;

    protected $columnMapping = [];

    /**
     * @param Nette\Database\Table\Selection $selection
     * @param Nette\Database\Context $context
     */
    public function __construct(
        Nette\Database\Table\Selection $selection,
        $columnMapping = [],
        Nette\Database\Context $context = NULL
    )
    {
        $this->context = $context;
        $this->netteTable = $selection;
        $this->columnMapping = $columnMapping;
        $this->totalCount = $selection->count('*');
    }

    /**
     * @return Nette\Database\Table\Selection
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
     * @return int
     */
    public function count()
    {
        $count = $this->getSelection()->count('*');
        $toEnd = $count - ($this->offset + $this->limit);
        return !is_null($this->limit) && $this->limit < $count ? ($toEnd < $this->limit ? $toEnd : $this->limit) : $count;
    }



    /**
     * Get searched values with applied limit, offset and where
     * @return ArrayHash[]
     */
    public function fetchAll()
    {
        $selection = $this->getSelection();
        $this->lastFetchAllResult = [];
        $out = [];
        foreach ($selection->fetchAll() as $row) {
            /** @var Nette\Database\Table\ActiveRow $row */
            $this->lastFetchAllResult[] = $row;
            $out[] = $this->makeArrayHash($row->toArray());
        }
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
        return $this->netteTable->order($row . ' ' . $sorting);
    }

    /**
     * Return first element from data
     * @return ArrayHash|FALSE
     */
    public function fetch()
    {
        if ($this->totalCount > 0) {
            return $this->makeArrayHash(
                $this->getSelection(FALSE, FALSE)
                    ->fetch()
                    ->toArray()
            );
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
            ->select($this->prefixColumn($key))
            ->select($this->prefixColumn($value))
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

    public function setRelated($table, $column)
    {
        if (is_null($this->context)) {
            throw new Exception('Related require set Nette database context in constructor.');
        }
        $this->related[$table] = $column;
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
            throw new Exception('Relation for table ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table])) {
            $this->relations[$table] = $source = new static($this->context->table($table), $table, $this->context);
            $source->setPrimaryKey($this->related[$table][4]);
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

    protected function prefixColumn($column, $newPrefix = NULL)
    {
        if (isset($this->columnMapping[$column])) {
            return $this->columnMapping[$column];
        }

        if (!is_null($newPrefix)) {
            return $newPrefix . '.' . $column;
        }

        return $column;
    }

    protected function makeArrayHash(array $val)
    {
        return ArrayHash::from($val);
    }

    protected function getSelection($limit = TRUE, $where = TRUE)
    {
        $selection = clone $this->netteTable;
        if ($where) {
            foreach ($this->whereArr as $conditions) {
                call_user_func_array([$selection, 'where'], $conditions);
            }
        }
        if ($limit) {
            $selection->limit($this->limit, $this->offset);
        }
        return $selection;
    }

}
