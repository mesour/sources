<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 Martin Procházka <juniwalk@outlook.cz>, Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @author  Martin Procházka <juniwalk@outlook.cz>
 * @author  Matouš Němec <matous.nemec@mesour.com>
 */
class DoctrineSource implements ISource
{

    /**
     * Doctrine QueryBuilder instance.
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * Mapping of columns to QueryBuilder
     * @var array
     */
    protected $columnMapping;

    /**
     * Count of all items.
     * @var int
     */
    protected $itemsTotalCount = 0;

    /**
     * Name of primary column name.
     * @var string
     */
    protected $primaryKey = 'id';

    protected $limit = NULL;

    protected $offset = 0;

    private $related = [];

    private $relations = [];

    private $whereArr = [];

    /** @var null|array */
    protected $lastFetchAllResult = NULL;

    /**
     * Initialize Doctrine data source with QueryBuilder instance.
     * @param QueryBuilder $queryBuilder Source of data
     * @param array $columnMapping       Column name mapper
     */
    public function __construct(QueryBuilder $queryBuilder, array $columnMapping = [])
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->columnMapping = $columnMapping;
    }

    /**
     * Get instance of the QueryBuilder.
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * Get copy of the QueryBuilder.
     * @param bool|FALSE $resetWhere
     * @param bool|FALSE $resetLimit
     * @return QueryBuilder
     */
    public function cloneQueryBuilder($resetWhere = FALSE, $resetLimit = FALSE)
    {
        $queryBuilder = clone $this->getQueryBuilder();
        if (!$resetWhere) {
            foreach ($this->whereArr as $item) {
                call_user_func_array([$queryBuilder, (isset($item[2]) && $item[2] ? 'orWhere' : 'andWhere')], [$item[0]]);
                if (count($item[1]) > 0) {
                    foreach ($item[1] as $key => $val) {
                        $queryBuilder->setParameter($key, $val);
                    }
                }
            }
        }
        if (!$resetLimit && is_numeric($this->limit)) {
            $queryBuilder->setMaxResults($this->limit);

            if ($this->offset > 0) {
                $queryBuilder->setFirstResult($this->offset);
            }
        }
        return $queryBuilder;
    }

    /**
     * Get Query instance from QueryBuilder.
     * @return Query
     */
    public function getQuery()
    {
        return $this->cloneQueryBuilder()->getQuery();
    }

    /**
     * Get current column mapping list.
     * @return array
     */
    public function getColumnMapping()
    {
        return $this->columnMapping;
    }

    /**
     * Get total count without applied WHERE and LIMIT.
     * @return int
     */
    public function getTotalCount()
    {
        if ($this->itemsTotalCount) {
            return $this->itemsTotalCount;
        }

        // Remove WHERE condition from QueryBuilder
        $query = $this->cloneQueryBuilder(TRUE, TRUE)
            ->getQuery();

        // Get total count without WHERE and LIMIT applied
        $this->itemsTotalCount = (new Paginator($query))->count();
        return $this->itemsTotalCount;
    }


    /**
     * Apply limit and offset.
     * @param  int $limit  Number of rows
     * @param  int $offset Rows to skip
     * @return static
     */
    public function applyLimit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }


    /**
     * Add where condition.
     * @param mixed $args
     * @param array $parameters key => $value
     * @param bool|FALSE $or
     * @return $this
     */
    public function where($args, array $parameters = [], $or = FALSE)
    {
        $this->whereArr[] = func_get_args();
        return $this;
    }


    /**
     * Add ORDER BY directive to the criteria.
     * @param  string $column  Column name
     * @param  string $sorting Sorting direction
     * @return static
     */
    public function orderBy($column, $sorting = 'ASC')
    {
        $this->getQueryBuilder()->addOrderBy($this->prefixColumn($column), $sorting);
        return $this;
    }


    /**
     * Get count with applied where without limit.
     * @return int
     */
    public function count()
    {
        $totalCount = $this->getTotalCount();
        if (!is_null($this->limit)) {
            if ($this->offset >= 0) {
                $offset = ($this->offset + 1);
                if ($totalCount - $offset <= 0) {
                    return 0;
                }
                if ($totalCount - $offset >= $this->limit) {
                    return $this->limit;
                } elseif ($totalCount - $offset < $this->limit) {
                    return $totalCount - $offset;
                }
            } else {
                if ($totalCount >= $this->limit) {
                    return $this->limit;
                } elseif ($totalCount < $this->limit) {
                    return $totalCount;
                }
            }
        }
        return (new Paginator($this->getQuery()))->count();
    }

    /**
     * Get first element from data.
     * @return array
     */
    public function fetch()
    {
        try {
            return $this->fixResult($this->cloneQueryBuilder()->setMaxResults(1)
                ->getQuery()->getSingleResult(Query::HYDRATE_ARRAY), TRUE);
        } catch (NoResultException $e) {
            return FALSE;
        }
    }

    /**
     * Get data with applied where, limit and offset.
     * @return array
     */
    public function fetchAll()
    {
        try {
            $this->lastFetchAllResult = $this->getQuery()->getResult();

            return $this->fixResult(
                $this->getEntityArrayAsArrays($this->lastFetchAllResult)
            );
        } catch (NoResultException $e) {
            return [];
        }
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

    /**
     * Get primary column name.
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Set new primary column name.
     * @param  string $column Column name
     * @return static
     */
    public function setPrimaryKey($column)
    {
        $this->primaryKey = $column;
        return $this;
    }

    public function fetchPairs($key, $value)
    {
        $results = $this->cloneQueryBuilder()
            ->select($this->prefixColumn($key), $this->prefixColumn($value))
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($results as $val) {
            $out[reset($val)] = end($val);
        }
        return $out;
    }

    public function setRelated($table, $key, $column, $as = NULL, $primary = 'id', $left = FALSE)
    {
        $path = explode('\\', $table);
        $shortClassName =  array_pop($path);
        $newPrefix = substr(strtolower($shortClassName), 0, 2);

        $this->related[$table] = [
            $table, $key, $column, $as, $primary, $left, $newPrefix
        ];

        $this->getQueryBuilder()
            ->addSelect($this->prefixColumn($column, $newPrefix) . ' ' . (!is_null($as) ? $as : ''))
            ->{$left ? 'leftJoin' : 'join'}(
                $table,
                $newPrefix,
                Query\Expr\Join::WITH,
                $this->prefixColumn($key) . ' = ' . $this->prefixColumn($primary, $newPrefix)
            );

        return $this;
    }

    public function related($table, $tablePrefix = 'abc')
    {
        if (!$this->isRelated($table)) {
            throw new Exception('Relation ' . $table . ' does not exists.');
        }
        if (!isset($this->relations[$table])) {
            $source = new static(
                $this->getQueryBuilder()->getEntityManager()
                    ->createQueryBuilder()->select($tablePrefix)
                    ->from($table, $tablePrefix), $this->columnMapping);
            $source->setPrimaryKey($this->related[$table][4]);
            $this->relations[$table] = $source;
        }
        return $this->relations[$table];
    }

    public function isRelated($table)
    {
        return isset($this->related[$table]);
    }

    public function getAllRelated()
    {
        return $this->related;
    }

    protected function getEntityArrayAsArrays($results)
    {
        $em = $this->getQueryBuilder()->getEntityManager();

        $out = [];
        foreach ($results as $result) {
            $addedColumns = [];
            if (is_array($result)) {
                $instance = reset($result);
                unset($result[0]);
                $addedColumns = $result;
            } else {
                $instance = $result;
            }

            $classMetaData = $em->getClassMetadata(get_class($instance));
            $fieldNames = $classMetaData->getFieldNames();

            $item = [];
            foreach ($fieldNames as $key => $fieldName) {
                $method = sprintf('get%s', ucwords($fieldName));
                $item[$fieldName] = $instance->{$method}();
            }
            if (count($addedColumns) > 0) {
                $item = array_merge($item, $addedColumns);
            }

            $out[] = $item;
        }
        return $out;
    }

    protected function fixResult(array $val, $fetch = FALSE)
    {
        if (count($val) > 0) {
            $hasSubArray = is_array(reset($val));

            if (!$hasSubArray) {
                return $this->makeArrayHash($val);
            }
            $out = [];
            if ($fetch) {
                $val = [$val];
            }
            foreach ($val as $item) {
                if (is_array($item)) {
                    foreach ($item as $key => $subItem) {
                        if (is_numeric($key) && is_array($subItem)) {
                            foreach ($subItem as $keyName => $subItemValue) {
                                $item[$keyName] = $subItemValue;

                            }
                            unset($item[$key]);
                        }
                    }
                }
                foreach ($item as $itemKey => $val) {
                    unset($item[$itemKey]);
                    $item[$itemKey] = $val;
                }
                $out[] = $this->makeArrayHash($item);
                if ($fetch) {
                    return reset($out);
                }
            }
            return $out;
        }
        return $val;
    }

    public function makeArrayHash(array $data)
    {
        return ArrayHash::from($data);
    }
    /*
        protected function getRealColumnName($column)
        {
            foreach ($this->columnMapping as $key => $item) {
                $parts = explode('.', $item);
                $name = end($parts);

                if ($column === $item || $column === $name) {
                    return $key;
                }
            }
            return $column;
        }
    */
    /**
     * Add prefix to the column name.
     * @param  string $column Column name
     * @param string|null $newPrefix
     * @return string
     */
    protected function prefixColumn($column, $newPrefix = NULL)
    {
        if (isset($this->columnMapping[$column])) {
            return $this->columnMapping[$column];
        }

        if (strpos($column, '.') !== false) {
            return $column;
        }

        if (!is_null($newPrefix)) {
            return $newPrefix . '.' . $column;
        }

        return current($this->getQueryBuilder()->getRootAliases()) . '.' . $column;
    }

}