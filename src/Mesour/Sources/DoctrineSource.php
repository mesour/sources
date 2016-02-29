<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Martin Procházka <juniwalk@outlook.cz>, Matouš Němec (http://mesour.com)
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
class DoctrineSource extends BaseSource
{

    /** @var QueryBuilder */
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


    protected $limit = null;

    protected $offset = 0;

    private $whereArr = [];

    /**
     * Initialize Doctrine data source with QueryBuilder instance.
     * @param QueryBuilder $queryBuilder Source of data
     * @param array $columnMapping Column name mapper
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
    public function cloneQueryBuilder($resetWhere = false, $resetLimit = false)
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
        $query = $this->cloneQueryBuilder(true, true)
            ->getQuery();

        // Get total count without WHERE and LIMIT applied
        $this->itemsTotalCount = (new Paginator($query))->count();

        return $this->itemsTotalCount;
    }


    /**
     * Apply limit and offset.
     * @param  int $limit Number of rows
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
    public function where($args, array $parameters = [], $or = false)
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
                ->getQuery()->getSingleResult(Query::HYDRATE_ARRAY), true);
        } catch (NoResultException $e) {
            return false;
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

    public function getReferencedSource($table, $callback = null, $tablePrefix = '_a0')
    {
        return parent::getReferencedSource($table, $callback ? $callback : function () use ($table, $tablePrefix) {
            return new static(
                $this->getQueryBuilder()->getEntityManager()
                    ->createQueryBuilder()->select($tablePrefix)
                    ->from($table, $tablePrefix), $this->columnMapping);
        });
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

    protected function fixResult(array $val, $fetch = false)
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

    /**
     * Add prefix to the column name.
     * @param  string $column Column name
     * @param string|null $newPrefix
     * @return string
     */
    protected function prefixColumn($column, $newPrefix = null)
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