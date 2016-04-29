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
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mesour\Sources\Structures\Columns\IColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToManyColumnStructure;
use Mesour\Sources\Structures\Columns\ManyToOneColumnStructure;
use Mesour\Sources\Structures\Columns\OneToManyColumnStructure;
use Mesour\Sources\Structures\Columns\OneToOneColumnStructure;

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
			return $result;
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
			$table,
			$callback ? $callback : function () use ($table, $tablePrefix) {
				$tableStructure = $this->getDataStructure()->getTableStructure($table);
				$source = new static(
					$tableStructure->getName(),
					$tableStructure->getPrimaryKey(),
					$this->getQueryBuilder()->getEntityManager()
						->createQueryBuilder()->select($tablePrefix)
						->from($table, $tablePrefix)
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

		return $this->determineFromColumns(
			$this->queryBuilder->getEntityManager()->getClassMetadata($table)
		);
	}

	protected function initializeDataStructure($tableName, $primaryKey)
	{
		$dataStructure = new Structures\DataStructure($tableName, $primaryKey);

		$this->setDataStructure($dataStructure);

		$entityManager = $this->queryBuilder->getEntityManager();

		$classMetadata = $entityManager->getClassMetadata($tableName);

		Helpers::setStructureFromColumns($dataStructure, $this->determineFromColumns($classMetadata));

		foreach ($classMetadata->getAssociationNames() as $associationName) {
			$targetClass = $classMetadata->getAssociationTargetClass($associationName);
			$targetMetadata = $entityManager->getClassMetadata($targetClass);

			$associations = $classMetadata->getAssociationsByTargetClass($targetClass);

			$primaryColumns = $targetMetadata->getIdentifierColumnNames();

			foreach ($associations as $association) {
				$dataStructure->getOrCreateTableStructure($targetClass, reset($primaryColumns));

				$associationsType = $association['type'];
				if ($associationsType === $classMetadata::ONE_TO_ONE) {
					if ($association['sourceEntity'] === $tableName) {
						$referencedColumn = reset($association['targetToSourceKeyColumns']);
					}
					if (!isset($referencedColumn)) {
						throw new InvalidStateException(
							sprintf(
								'Can not find referenced column name for field %s.',
								$associationName
							)
						);
					}
					$dataStructure->addOneToOne($associationName, $targetClass, $referencedColumn);
				} elseif ($associationsType === $classMetadata::MANY_TO_ONE) {
					if ($association['sourceEntity'] === $tableName) {
						$referencedColumn = reset($association['targetToSourceKeyColumns']);
					}
					if (!isset($referencedColumn)) {
						throw new InvalidStateException(
							sprintf(
								'Can not find referenced column name for field %s.',
								$associationName
							)
						);
					}
					$dataStructure->addManyToOne($associationName, $targetClass, $referencedColumn);
				} elseif ($associationsType === $classMetadata::ONE_TO_MANY) {
					foreach ($targetMetadata->getAssociationMappings() as $joinedAssociation) {
						if ($joinedAssociation['inversedBy'] === $associationName) {
							$referencedColumn = reset($joinedAssociation['targetToSourceKeyColumns']);
						}
					}
					if (!isset($referencedColumn)) {
						throw new InvalidStateException(
							sprintf(
								'Can not find referenced column name for field %s. Is `inversedBy` set in property annotation?',
								$associationName
							)
						);
					}
					$dataStructure->addOneToMany($associationName, $targetClass, $referencedColumn);
				} elseif ($associationsType === $classMetadata::MANY_TO_MANY) {
					if (!isset($association['joinTable'])) {
						throw new InvalidStateException(
							sprintf(
								'Can not find joinColumn settings for field %s. Is `joinColumn` set in property annotation?',
								$associationName
							)
						);
					}
					$joinTable = $association['joinTable'];
					if (isset($joinTable['joinColumns']) && $joinTable['joinColumns']) {
						$joinColumn = reset($joinTable['joinColumns']);
						$inverseJoinColumn = reset($joinTable['inverseJoinColumns']);
						$dataStructure->addManyToMany(
							$associationName,
							$targetClass,
							$inverseJoinColumn['name'],
							$joinTable['name'],
							$joinColumn['name']
						);
					}
				}
			}
		}
	}

	private function determineFromColumns(ClassMetadata $classMetadata)
	{
		$out = [];
		/** @var Column $column */
		foreach ($classMetadata->getFieldNames() as $fieldName) {
			$fieldMapping = [];
			try {
				$fieldMapping = $classMetadata->getFieldMapping($fieldName);
			} catch (MappingException $e) {
				$fieldMapping['type'] = 'undefined';
			}
			$columnName = $classMetadata->getColumnName($fieldName);
			$type = $classMetadata->getTypeOfField($fieldName);
			$isNullable = $classMetadata->isNullable($fieldName);

			if (strtolower($type) === 'enum') {
				$out[$columnName] = [
					'type' => IColumnStructure::ENUM,
				];
				$enum = $fieldMapping['columnDefinition'];
				$options = str_getcsv(str_replace('enum(', '', substr($enum, 0, strlen($enum) - 1)), ',', "'");

				$out[$columnName]['values'] = [];
				foreach ($options as $option) {
					$out[$columnName]['values'][] = $option;
				}
			} else {
				switch ($type) {
					case Type::STRING:
					case Type::TEXT:
						$out[$columnName] = [
							'type' => IColumnStructure::TEXT,
						];
						break;
					case Type::INTEGER:
					case Type::FLOAT:
					case Type::SMALLINT:
					case Type::BIGINT:
					case Type::DECIMAL:
						$out[$columnName] = [
							'type' => IColumnStructure::NUMBER,
						];
						break;
					case Type::DATETIME:
					case Type::DATETIMETZ:
					case Type::DATE:
					case Type::TIME:
						$out[$columnName] = [
							'type' => IColumnStructure::DATE,
						];
						break;
					case Type::BOOLEAN:
						$out[$columnName] = [
							'type' => IColumnStructure::BOOL,
						];
						break;
				}
			}
			if (isset($out[$columnName])) {
				$out[$columnName]['nullable'] = $isNullable;
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
			foreach ($val as $key => $item) {
				$current = $item;
				foreach ($this->getDataStructure()->getColumns() as $column) {
					if ($column instanceof OneToOneColumnStructure || $column instanceof ManyToOneColumnStructure) {
						$items = [$current[$column->getName()]];
						$this->addPatternToRows($column, $items);
						$current[$column->getName()] = reset($items);
					} elseif (
						$column instanceof ManyToManyColumnStructure
						|| $column instanceof OneToManyColumnStructure
					) {
						$this->addPatternToRows($column, $current[$column->getName()]);
					}
				}
				$arrayHash = $this->makeArrayHash($current);
				if ($fetch) {
					return $arrayHash;
				}
				$out[] = $arrayHash;
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
