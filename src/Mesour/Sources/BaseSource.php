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
			throw new InvalidArgumentException('Table structure ' . $table . ' does not exists.');
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
		return ArrayHash::from($val);
	}

	protected function initializeDataStructure($tableName, $primaryKey)
	{
		$dataStructure = new Structures\DataStructure($tableName, $primaryKey);

		$this->setDataStructure($dataStructure);
	}

	/**
	 * @param string $primaryKey
	 * @return $this
	 * @deprecated
	 */
	public function setPrimaryKey($primaryKey)
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
		return $this;
	}

	/**
	 * @param string $columnAlias
	 * @param string $table
	 * @param string $referencedColumn
	 * @param string $primaryKey
	 * @return $this
	 * @deprecated
	 */
	public function setReference($columnAlias, $table, $referencedColumn, $primaryKey = 'id')
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
		return $this;
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getReferenceSettings()
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
		return [];
	}

	/**
	 * @param string $table
	 * @return bool
	 * @deprecated
	 */
	public function hasReference($table)
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
		return false;
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getReferencedTables()
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
		return [];
	}

	/**
	 * @param string $columnAlias
	 * @deprecated
	 */
	public function getReference($columnAlias)
	{
		trigger_error('Method set primary key is deprecated, use DataStructure.', E_USER_DEPRECATED);
	}

}
