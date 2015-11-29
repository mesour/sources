<?php

namespace Mesour\Sources;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @author  Martin Procházka <juniwalk@outlook.cz>
 * @package Mesour DataGrid
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
     * Count of filtered items.
     * @var int
     */
    protected $itemsCount = 0;

    /**
     * Name of primary column name.
     * @var string
     */
    protected $primaryKey = 'id';

    private $related = [];

    private $relations = [];

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
     * @return QueryBuilder
     */
    public function cloneQueryBuilder()
    {
        return clone $this->queryBuilder;
    }

    /**
     * Get Query instance from QueryBuilder.
     * @return Query
     */
    public function getQuery()
    {
        return $this->queryBuilder->getQuery();
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
        $query = $this->cloneQueryBuilder()
            ->resetDQLPart('where')
            ->setParameters([])// May cause problems?
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
        return $this->cloneQueryBuilder()
            ->resetDQLPart('where')
            ->setParameters([])// May cause problems?
            ->setMaxResults(null)
            ->setFirstResult(null)
            ->getQuery()->getArrayResult();
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
     * @return $this
     */
    public function where($args, array $parameters = [])
    {
        call_user_func_array([$this->getQueryBuilder(), 'where'], $args);
        $this->getQueryBuilder()->setParameters($parameters);
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
        if (!$this->itemsCount) {
            $this->itemsCount = (new Paginator($this->getQuery()))->count();
        }
        return $this->itemsCount;
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