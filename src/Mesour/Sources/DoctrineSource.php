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

    private $related = [];

    private $relations = [];

    private $whereArr = [];

    /**
     * Initialize Doctrine data source with QueryBuilder instance.
     * @param QueryBuilder $queryBuilder Source of data
     * @param array $columnMapping Column name mapper
     */
    public function __construct(QueryBuilder $queryBuilder, array $columnMapping = [])
    {
        // Save copy of provided QueryBuilder
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
     * @return QueryBuilder
     */
    public function cloneQueryBuilder($resetWhere = FALSE)
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

        // Remove WHERE confition from QueryBuilder
        $query = $this->cloneQueryBuilder(TRUE)
            ->getQuery();

        // Get total count without WHERE and LIMIT applied
        $this->itemsTotalCount = (new Paginator($query))->count();
        return $this->itemsTotalCount;
    }

    /**
     * Get every single row of the table.
     * @return array
     */
    public function fetchFullData()
    {
        //todo: move to filter source
        return $this->fixResult($this->cloneQueryBuilder(TRUE)
            ->setMaxResults(null)
            ->setFirstResult(null)
            ->getQuery()->getArrayResult());
    }


    /**
     * Apply limit and offset.
     * @param  int $limit Number of rows
     * @param  int $offset Rows to skip
     * @return static
     */
    public function applyLimit($limit, $offset = 0)
    {
        $this->getQueryBuilder()->setMaxResults($limit)->setFirstResult($offset);
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
     * @param  string $column Column name
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
            return $this->fixResult($this->getQuery()->getArrayResult());

        } catch (NoResultException $e) {
            return [];
        }
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
        $newPrefix = substr($table, 0, 2);

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

    protected function fixResult(array $val, $fetch = FALSE)
    {
        if (count($val) > 0) {
            $hasSubArray = is_array(reset($val));

            if (!$hasSubArray) {
                return ArrayHash::from($val);
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
                    $item[$this->getRealColumnName($itemKey)] = $val;
                }
                $out[] = ArrayHash::from($item);
                if ($fetch) {
                    return reset($out);
                }
            }
            return $out;
        }
        return $val;
    }

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