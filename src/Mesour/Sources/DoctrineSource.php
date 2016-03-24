<?php
/**
 * This file is part of the Mesour Sources (http://components.mesour.com/component/sources)
 *
 * Copyright (c) 2015 - 2016 Martin Procházka <juniwalk@outlook.cz>, Matouš Němec (http://mesour.com)
 *
 * For full licence and copyright please view the file licence.md in root of this project
 */

namespace Mesour\Sources;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mesour\Sources\Structures\Columns\IColumnStructure;

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

	public function __construct($tableName, $primaryKey, QueryBuilder $queryBuilder, array $columnMapping = [])
	{
		$this->queryBuilder = clone $queryBuilder;
		$this->columnMapping = $columnMapping;

		parent::__construct($tableName, $primaryKey);
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

		$query = $this->cloneQueryBuilder(true, true)
			->getQuery();

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
			$entity = $this->cloneQueryBuilder()->setMaxResults(1)
				->getQuery()->getSingleResult();
			if (!$entity) {
				return false;
			}
			$result = $this->fixResult($this->getEntityArrayAsArrays([$entity]), true);
			return reset($result);
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
		return parent::getReferencedSource(
			$table, $callback ? $callback : function () use ($table, $tablePrefix) {
			$tableStructure = $this->getDataStructure()->getTableStructure($table);
			$source = new static(
				$tableStructure->getName(),
				$tableStructure->getPrimaryKey(),
				$this->getQueryBuilder()->getEntityManager()
					->createQueryBuilder()->select($tablePrefix)
					->from($table, $tablePrefix),
				$this->columnMapping
			);
			$source->setDataStructure($tableStructure);
			return $source;
		}
		);
	}

	public function getTableColumns($table, $internal = false)
	{
		if (
			!$internal
			&& ($this->getDataStructure()->hasTableStructure($table) || $table === $this->getTableName())
		) {
			return parent::getTableColumns($table, $internal);
		}
		$columns = $this->queryBuilder->getEntityManager()->getConnection()->getSchemaManager()
			->listTableColumns($this->getTableNameFromClassName($table));
		return $this->determineFromColumns($columns, $table);
	}

	protected function initializeDataStructure($tableName, $primaryKey)
	{
		$dataStructure = new Structures\DataStructure($tableName, $primaryKey);

		$this->setDataStructure($dataStructure);

		$entityManager = $this->queryBuilder->getEntityManager();
		$schemaManager = $entityManager->getConnection()->getSchemaManager();

		$tables = $schemaManager->listTables();
		$columns = $schemaManager->listTableColumns($this->getTableNameFromClassName($tableName));

		$determinedColumns = $this->determineFromColumns($columns, $tableName);

		Helpers::setStructureFromColumns($dataStructure, $determinedColumns);

		$classMetaData = $this->getQueryBuilder()->getEntityManager()->getClassMetadata($tableName);

		foreach ($classMetaData->getAssociationMappings() as $associationMapping) {
			$targetEntity = $associationMapping['targetEntity'];

			$currentTableInstance = null;
			foreach ($tables as $_table) {
				if ($_table->getName() === $this->getTableNameFromClassName($targetEntity)) {
					$currentTableInstance = $_table;
					break;
				}
			}
			if (!$currentTableInstance) {
				throw new TableNotExistException("Table for entity $targetEntity not exists.");
			}

			$primaryColumns = $currentTableInstance->getPrimaryKey()->getColumns();
			$dataStructure->getOrCreateTableStructure($targetEntity, reset($primaryColumns));
		}
	}

	private function determineFromColumns(array $columns, $table)
	{
		$classMetaData = $this->getQueryBuilder()->getEntityManager()->getClassMetadata($table);

		$out = [];
		/** @var Column $column */
		foreach ($columns as $column) {
			$fieldMapping = [];
			try {
				$fieldMapping = $classMetaData->getFieldMapping($classMetaData->getFieldName($column->getName()));
			} catch (MappingException $e) {
				$fieldMapping['type'] = 'undefined';
			}

			if (strtolower($fieldMapping['type']) === 'enum') {
				$out[$column->getName()] = [
					'type' => IColumnStructure::ENUM,
				];
				$enum = $fieldMapping['columnDefinition'];
				$options = str_getcsv(str_replace('enum(', '', substr($enum, 0, strlen($enum) - 1)), ',', "'");

				$out[$column->getName()]['values'] = [];
				foreach ($options as $option) {
					$out[$column->getName()]['values'][] = $option;
				}
			} else {
				$type = $column->getType()->getName();

				switch ($type) {
					case Type::STRING :
					case Type::TEXT :
						$out[$column->getName()] = [
							'type' => IColumnStructure::TEXT,
						];
						break;
					case Type::INTEGER :
					case Type::FLOAT :
					case Type::SMALLINT :
					case Type::BIGINT :
					case Type::DECIMAL :
						$out[$column->getName()] = [
							'type' => IColumnStructure::NUMBER,
						];
						break;
					case Type::DATETIME :
					case Type::DATETIMETZ :
					case Type::DATE :
					case Type::TIME :
						$out[$column->getName()] = [
							'type' => IColumnStructure::DATE,
						];
						break;
					case Type::BOOLEAN :
						$out[$column->getName()] = [
							'type' => IColumnStructure::BOOL,
						];
						break;
				}
			}
		}
		return $out;
	}

	/**
	 * @param string $table Table name
	 * @return string Entity class name, null if not found
	 */
	protected function getTableNameFromClassName($table)
	{
		return $this->getQueryBuilder()->getEntityManager()
			->getClassMetadata($table)
			->getTableName();
	}

	protected function getClassNameFromTableName($table)
	{
		$em = $this->getQueryBuilder()->getEntityManager();
		$classNames = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		foreach ($classNames as $className) {
			$classMetaData = $em->getClassMetadata($className);
			if ($table == $classMetaData->getTableName()) {
				return $classMetaData->getName();
			}
		}
		return null;
	}

	protected function getEntityArrayAsArrays(array $results)
	{
		$out = [];
		foreach ($results as $entity) {
			if (method_exists($entity, 'toArray')) {
				$out[] = $entity->toArray();
			} else {
				throw new InvalidStateException(
					sprintf('Entity %s must have method toArray.', get_class($entity))
				);
			}
		}
		return $out;
	}

	protected function fixResult(array $val, $fetch = false)
	{
		if (count($val) > 0) {
			$out = [];
			if ($fetch) {
				$val = [$val];
			}
			foreach ($val as $item) {
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
