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
use Mesour\Sources\Structures\Columns;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
class ArraySource extends BaseSource
{

	const DATE_MARK = '__date_';

	/** @var Mesour\ArrayManage\Searcher\Select */
	protected $select;

	protected $referencedData = [];

	protected $dataArr = [];

	public function __construct($tableName, $primaryKey, array $data = [], array $referencedData = [])
	{
		parent::__construct($tableName, $primaryKey);

		if (!class_exists(Mesour\ArrayManage\Searcher\Select::class)) {
			throw new MissingRequiredException('Array data source required composer package "mesour/array-manager".');
		}
		$this->dataArr = $data;
		$this->referencedData = $referencedData;
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
		if (
			$this->getDataStructure()->hasColumn($column)
			&& $this->getDataStructure()->getColumn($column)->getType() === Columns\IColumnStructure::DATE
		) {
			$value = $this->fixDate($value);
			$column = self::DATE_MARK . $column;
		}

		$this->getSelect()->where($column, $value, $condition, $operator);

		return $this;
	}

	public static function fixDate($date)
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
		foreach ($out as $key => $val) {
			$this->removeStructureDate($out[$key]);
		}
		foreach ($out as $key => $val) {
			$out[$key] = $this->makeArrayHash($val);
		}
		$this->lastFetchAllResult = $out;

		return $out;
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
		$this->removeStructureDate($data);

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

	public function joinField($table, $key, $column, $columnAlias, $left = false)
	{
		$source = $this->getReferencedSource($table);
		foreach ($this->dataArr as $currentKey => $item) {
			/** @var ISource $currentSource */
			$currentSource = clone $source;
			$itemName = is_string($columnAlias) ? $columnAlias : $column;
			if (isset($item[$key])) {
				$currentItem = $currentSource
					->where($source->getPrimaryKey(), $item[$key], Mesour\ArrayManage\Searcher\Condition::EQUAL)
					->fetch();
				if (isset($currentItem[$column])) {
					$this->dataArr[$currentKey][$itemName] = $currentItem[$column];
				} else {
					$this->dataArr[$currentKey][$itemName] = null;
				}
				$this->select = null;
			} elseif ($left) {
				$this->dataArr[$currentKey][$itemName] = null;
			} else {
				throw new Exception('Column ' . $key . ' does not exist in data array.');
			}
			unset($currentSource);
		}

		return $this;
	}

	public function attachOneToOne(Columns\OneToOneColumnStructure $columnStructure, $left = false)
	{
		$this->attachToOneRelation($columnStructure, $left);
		return $this;
	}

	public function attachManyToOne(Columns\ManyToOneColumnStructure $columnStructure, $left = false)
	{
		$this->attachToOneRelation($columnStructure, $left);
		return $this;
	}

	public function attachOneToMany(Columns\OneToManyColumnStructure $columnStructure, $left = false)
	{
		$source = $this->getReferencedSource($columnStructure->getTableStructure()->getName());
		foreach ($this->dataArr as $currentKey => $item) {
			/** @var ISource $currentSource */
			$currentSource = clone $source;
			if (isset($item[$this->getPrimaryKey()])) {
				$innerItems = $currentSource
					->where(
						$columnStructure->getReferencedColumn(),
						$item[$this->getPrimaryKey()],
						Mesour\ArrayManage\Searcher\Condition::EQUAL
					)
					->fetchAll();

				$this->addPatternToRows($columnStructure, $innerItems);

				$this->dataArr[$currentKey][$columnStructure->getName()] = $innerItems;
			} elseif ($left) {
				$this->dataArr[$currentKey][$columnStructure->getName()] = [];
			} else {
				throw new Exception(
					'Column ' . $columnStructure->getReferencedColumn() . ' does not exist in data array.'
				);
			}
			unset($currentSource);
		}

		return $this;
	}

	public function attachManyToMany(Columns\ManyToManyColumnStructure $columnStructure, $left = false)
	{
		$source = $this->getReferencedSource($columnStructure->getReferencedTable());
		foreach ($this->dataArr as $currentKey => $item) {
			/** @var ISource $currentSource */
			$currentSource = clone $source;
			if (isset($item[$this->getPrimaryKey()])) {
				$innerItems = $currentSource
					->where(
						$columnStructure->getReferencedColumn(),
						$item[$this->getPrimaryKey()],
						Mesour\ArrayManage\Searcher\Condition::EQUAL
					)
					->fetchAll();

				if (count($innerItems) === 0) {
					$this->dataArr[$currentKey][$columnStructure->getName()] = [];
					unset($currentSource);
					continue;
				}

				$itemSource = clone $this->getReferencedSource($columnStructure->getTableStructure()->getName());
				foreach ($innerItems as $current) {
					$itemSource->where(
						$columnStructure->getTableStructure()->getPrimaryKey(),
						$current[$columnStructure->getSelfColumn()],
						Mesour\ArrayManage\Searcher\Condition::EQUAL,
						'or'
					);
				}

				$innerItems = $itemSource->fetchAll();
				$this->addPatternToRows($columnStructure, $innerItems);

				$this->dataArr[$currentKey][$columnStructure->getName()] = $innerItems;
			} elseif ($left) {
				$this->dataArr[$currentKey][$columnStructure->getName()] = [];
			} else {
				throw new Exception('Primary column ' . $this->getPrimaryKey() . ' not exists in data array.');
			}
			unset($currentSource);
		}

		return $this;
	}

	public function getReferencedSource($table, $callback = null)
	{
		return parent::getReferencedSource(
			$table,
			$callback ? $callback : function () use ($table) {
				if (!isset($this->referencedData[$table])) {
					throw new InvalidStateException('Array with key does not exists in secon __construct parameter.');
				}
				$tableStructure = $this->getDataStructure()->getTableStructure($table);
				return new static(
					$tableStructure->getName(),
					$tableStructure->getPrimaryKey(),
					$this->referencedData[$table]
				);
			}
		);
	}

	/**
	 * @return Mesour\ArrayManage\Searcher\Select
	 * @throws Exception
	 */
	protected function getSelect()
	{
		if (!$this->select) {
			foreach ($this->getDataStructure()->getColumns() as $column) {
				if ($column->getType() === Columns\IColumnStructure::DATE) {
					foreach ($this->dataArr as $key => $item) {
						if (!array_key_exists($column->getName(), $item)) {
							throw new Exception('Column ' . $column->getName() . ' does not exists in source array.');
						}
						$this->dataArr[$key][self::DATE_MARK . $column->getName()] = $this->fixDate(
							$item[$column->getName()]
						);
					}
				}
			}
			$this->select = new Mesour\ArrayManage\Searcher\Select($this->dataArr);
		}

		return $this->select;
	}

	protected function removeStructureDate(&$out)
	{
		foreach ($this->getDataStructure()->getColumns() as $column) {
			if ($column->getType() === Columns\IColumnStructure::DATE) {
				unset($out[self::DATE_MARK . $column->getName()]);
			}
		}
	}

	protected function attachToOneRelation(Columns\BaseTableColumnStructure $columnStructure, $left = false)
	{
		$source = $this->getReferencedSource($columnStructure->getTableStructure()->getName());
		foreach ($this->dataArr as $currentKey => $item) {
			/** @var ISource $currentSource */
			$currentSource = clone $source;
			if (isset($item[$this->getPrimaryKey()])) {
				if (!array_key_exists($columnStructure->getReferencedColumn(), $item)) {
					throw new InvalidStateException(
						sprintf(
							'Referenced column "%s" does not exist on "%s" table.',
							$columnStructure->getReferencedColumn(),
							$this->getTableName()
						)
					);
				}
				$innerItem = $currentSource
					->where(
						$columnStructure->getTableStructure()->getPrimaryKey(),
						$item[$columnStructure->getReferencedColumn()],
						Mesour\ArrayManage\Searcher\Condition::EQUAL
					)
					->fetch();

				$innerItems = [$innerItem];
				$this->addPatternToRows($columnStructure, $innerItems);

				$this->dataArr[$currentKey][$columnStructure->getName()] = reset($innerItems);
			} elseif ($left) {
				$this->dataArr[$currentKey][$columnStructure->getName()] = null;
			} else {
				throw new Exception(
					'Column ' . $columnStructure->getReferencedColumn() . ' does not exist in data array.'
				);
			}
			unset($currentSource);
		}
	}

}
