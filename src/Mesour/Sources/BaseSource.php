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

	private $primaryKey = 'id';

	private $references = [];

	private $referencedTables = [];

	private $referencedSources = [];

	/** @var null|array */
	protected $lastFetchAllResult = null;

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

	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}

	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	public function setReference($columnAlias, $table, $referencedColumn, $primaryKey = 'id')
	{
		if (!isset($this->references[$columnAlias])) {
			$this->addReferencedTable($table, $primaryKey);
			$this->references[$columnAlias] = [
				'table' => $table,
				'column' => $referencedColumn,
				'primary' => $primaryKey,
			];
		} else {
			throw new InvalidArgumentException('Reference for this column alias already exist.');
		}

		return $this;
	}

	/**
	 * @param $table
	 * @return bool
	 */
	public function getReference($columnAlias)
	{
		if (!isset($this->references[$columnAlias])) {
			throw new InvalidArgumentException(
				sprintf('Reference for column alias %s does not exist.', $columnAlias)
			);
		}
		return $this->references[$columnAlias];
	}

	public function getReferencedTables()
	{
		return $this->referencedTables;
	}

	/**
	 * @param $table
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public function getReferencedSource($table, $callback = null)
	{
		if (isset($this->referencedSources[$table])) {
			return $this->referencedSources[$table];
		}

		if (!$this->hasReference($table)) {
			throw new InvalidArgumentException('Relation ' . $table . ' does not exists.');
		}
		if (!is_callable($callback)) {
			throw new InvalidArgumentException(
				sprintf('Second parameter must be callable callback. %s given.', $callback)
			);
		}

		$source = call_user_func($callback);
		if (!$source instanceof ISource) {
			throw new InvalidArgumentException(
				sprintf('Callback must return instance of %s. %s given.', Isource::class, $callback)
			);
		}
		$this->referencedSources[$table] = $source;
		$source->setPrimaryKey($this->referencedTables[$table]);

		return $source;
	}

	/**
	 * @return array
	 */
	public function getReferenceSettings()
	{
		return $this->references;
	}

	/**
	 * @param $table
	 * @return bool
	 */
	public function hasReference($table)
	{
		return isset($this->referencedTables[$table]);
	}

	protected function addReferencedTable($table, $primaryKey = 'id')
	{
		$this->referencedTables[$table] = $primaryKey;
		return $this;
	}

	protected function makeArrayHash(array $val)
	{
		return ArrayHash::from($val);
	}

}