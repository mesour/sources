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
use Mesour\Sources\Structures\Columns\BaseTableColumnStructure;

/**
 * @author Matouš Němec <matous.nemec@mesour.com>
 */
abstract class BaseSource implements ISource
{

	/**
	 * @var Structures\DataStructure
	 */
	protected $dataStructure;

	private $referencedSources = [];

	/** @var null|array */
	protected $lastFetchAllResult = null;

	public function __construct($tableName, $primaryKey)
	{
		$this->initializeDataStructure($tableName, $primaryKey);
	}

	/**
	 * @return Structures\DataStructure
	 */
	public function getDataStructure()
	{
		return $this->dataStructure;
	}

	/**
	 * Get raw data from last fetchAll()
	 *
	 * IMPORTANT! fetchAll() must be called before call this method
	 *
	 * @return mixed
	 * @throws InvalidStateException
	 */
	public function fetchLastRawRows()
	{
		if (is_null($this->lastFetchAllResult)) {
			throw new InvalidStateException('Must call fetchAll() before call fetchLastRawRows() method.');
		}
		return $this->lastFetchAllResult;
	}

	public function getPrimaryKey()
	{
		return $this->getDataStructure()->getPrimaryKey();
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->getDataStructure()->getName();
	}

	/**
	 * @param string $table
	 * @param null|callable $callback
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function getReferencedSource($table, $callback = null)
	{
		if (isset($this->referencedSources[$table])) {
			return $this->referencedSources[$table];
		}

		if (!$this->getDataStructure()->hasTableStructure($table)) {
			throw new InvalidArgumentException(
				'Table structure ' . $table . ' does not exists. Try use method addTableToStructure on source.'
			);
		}
		if (!is_callable($callback)) {
			throw new InvalidArgumentException(
				sprintf('Second parameter must be callable callback. %s given.', $callback)
			);
		}

		$source = call_user_func($callback);
		if (!$source instanceof ISource) {
			throw new InvalidArgumentException(
				sprintf('Callback must return instance of %s. %s given.', ISource::class, $callback)
			);
		}
		$this->referencedSources[$table] = $source;

		return $source;
	}

	public function setDataStructure(Structures\ITableStructure $dataStructure)
	{
		$this->dataStructure = $dataStructure;
		if ($dataStructure instanceof Structures\IDataStructure) {
			$this->dataStructure->setSource($this);
		}
		return $this;
	}

	public function getTableColumns($table, $internal = false)
	{
		if (
			!$internal
			&& ($this->getDataStructure()->hasTableStructure($table) || $table === $this->getTableName())
		) {
			if ($table === $this->getTableName()) {
				$columns = $this->getDataStructure()->getColumns();
			} else {
				$columns = $this->getDataStructure()->getTableStructure($table)->getColumns();
			}
			if (count($columns) > 0) {
				return Helpers::getColumnsArrayFromStructure($columns);
			}
		}
		return [];
	}

	public function addTableToStructure($table, $primaryKey)
	{
		return $this->getDataStructure()->getOrCreateTableStructure($table, $primaryKey);
	}

	protected function makeArrayHash(array $val)
	{
		$hash = $val;

		$out = [];
		foreach ($hash as $key => $value) {
			if (is_numeric($key)) {
				$subOut = [];
				foreach ($value as $subKey => $subValue) {
					$subOut[$subKey] = $this->makeArrayHashNonRecursive($subValue);
				}
				$out[$key] = ArrayHash::from($subOut);
			} else {
				$value = $this->makeArrayHashNonRecursive($value);
				$out[$key] = $value;
			}
		}

		return ArrayHash::from($out);
	}

	private function makeArrayHashNonRecursive($value)
	{
		if (is_array($value) || $value instanceof ArrayHash) {
			$values = [];
			$firstValue = reset($value);
			if (is_array($firstValue) || $firstValue instanceof ArrayHash) {
				foreach ($value as $subValue) {
					$values[] = PatternedArrayHash::from($subValue, false);
				}
				return $values;
			} else {
				return PatternedArrayHash::from($value, false);
			}
		}
		return $value;
	}

	protected function initializeDataStructure($tableName, $primaryKey)
	{
		$dataStructure = new Structures\DataStructure($tableName, $primaryKey);

		$this->setDataStructure($dataStructure);
	}

	protected function addPatternToRows(BaseTableColumnStructure $columnStructure, &$items)
	{
		foreach ($items as $key => $item) {
			if ($columnStructure->getPattern()) {
				$item['_pattern'] = Helpers::parseValue($columnStructure->getPattern(), $item);
			} else {
				$item['_pattern'] = null;
			}
			$items[$key] = $item;
		}
	}

}
